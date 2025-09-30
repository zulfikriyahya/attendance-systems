import http from "k6/http";
import { check, sleep, group } from "k6";
import { Rate, Trend, Counter } from "k6/metrics";

// Custom metrics
const errorRate = new Rate("errors");
const cacheHitTrend = new Trend("cache_response_time");
const dbHitTrend = new Trend("db_response_time");
const presensiCounter = new Counter("presensi_created");

// Konfigurasi
const BASE_URL = __ENV.BASE_URL || "http://192.168.1.4:8000/api";
const API_TOKEN = __ENV.API_TOKEN || "P@ndegl@ng_14012000*"; // Jika pakai auth

// Data testing
const RFID_PEGAWAI = [
  "1234567890",
  "1234567891",
  "1234567892",
  "1234567893",
  "1234567894",
];
const RFID_SISWA = [
  "9876543210",
  "9876543211",
  "9876543212",
  "9876543213",
  "9876543214",
];
const ALL_RFIDS = [...RFID_PEGAWAI, ...RFID_SISWA];

// Konfigurasi Load Test
export const options = {
  stages: [
    // Warm-up: Build cache
    { duration: "30s", target: 10 }, // Warming up cache

    // Load Test: Test dengan cache
    { duration: "1m", target: 50 }, // Ramp up ke 50 users
    { duration: "2m", target: 100 }, // Ramp up ke 100 users
    { duration: "2m", target: 100 }, // Stay at 100 users (test cache performance)

    // Stress Test
    { duration: "1m", target: 200 }, // Spike to 200 users
    { duration: "1m", target: 200 }, // Hold spike

    // Cool down
    { duration: "30s", target: 0 }, // Ramp down
  ],

  thresholds: {
    http_req_duration: ["p(95)<500", "p(99)<1000"], // 95% request < 500ms
    http_req_failed: ["rate<0.01"], // Error rate < 1%
    errors: ["rate<0.05"], // Custom error rate < 5%
    cache_response_time: ["p(95)<100"], // Cache response < 100ms
  },
};

// Setup: Jalankan sekali di awal
export function setup() {
  console.log("🚀 Starting K6 Load Test for Presensi API");
  console.log(`📍 Base URL: ${BASE_URL}`);

  // Health check
  // const healthRes = http.get(`${BASE_URL}/health`);
  // if (healthRes.status !== 200) {
  //   throw new Error("API Health Check Failed!");
  // }

  // console.log("✅ API is healthy, starting test...\n");
  return { startTime: Date.now() };
}

// Helper function: Get headers
function getHeaders() {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };

  if (API_TOKEN) {
    headers["Authorization"] = `Bearer ${API_TOKEN}`;
  }

  return headers;
}

// Helper: Random RFID
function getRandomRFID() {
  return ALL_RFIDS[Math.floor(Math.random() * ALL_RFIDS.length)];
}

// Main test scenario
export default function () {
  const headers = getHeaders();

  // Scenario 1: Get Jadwal Hari Ini (Should hit cache after first request)
  group("Get Jadwal Hari Ini", () => {
    const startTime = Date.now();
    const res = http.get(`${BASE_URL}/jadwal-hari-ini`, { headers });
    const duration = Date.now() - startTime;

    const success = check(res, {
      "status is 200": (r) => r.status === 200,
      "has jadwal data": (r) => {
        const body = JSON.parse(r.body);
        return body.data && body.data.jam_datang;
      },
      "response time < 200ms": (r) => r.timings.duration < 200,
    });

    errorRate.add(!success);

    // Track if this is likely a cache hit (very fast response)
    if (duration < 50) {
      cacheHitTrend.add(duration);
    } else {
      dbHitTrend.add(duration);
    }
  });

  sleep(0.5);

  // Scenario 2: Validate RFID (Should be cached)
  group("Validate RFID", () => {
    const rfid = getRandomRFID();
    const startTime = Date.now();

    const res = http.post(
      `${BASE_URL}/validate-rfid`,
      JSON.stringify({ rfid: rfid }),
      { headers }
    );

    const duration = Date.now() - startTime;

    const success = check(res, {
      "status is 200": (r) => r.status === 200,
      "rfid is valid": (r) => {
        const body = JSON.parse(r.body);
        return body.status === "success";
      },
      "response time < 100ms": (r) => r.timings.duration < 100,
    });

    errorRate.add(!success);

    if (duration < 30) {
      cacheHitTrend.add(duration);
    } else {
      dbHitTrend.add(duration);
    }
  });

  sleep(0.3);

  // Scenario 3: Get Status Presensi (Should be cached)
  group("Get Status Presensi", () => {
    const rfid = getRandomRFID();
    const startTime = Date.now();

    const res = http.get(`${BASE_URL}/status-presensi/${rfid}`, { headers });
    const duration = Date.now() - startTime;

    const success = check(res, {
      "status is 200 or 404": (r) => r.status === 200 || r.status === 404,
      "has valid response": (r) => {
        const body = JSON.parse(r.body);
        return body.status && body.message;
      },
      "response time < 150ms": (r) => r.timings.duration < 150,
    });

    errorRate.add(!success);

    if (duration < 40) {
      cacheHitTrend.add(duration);
    } else {
      dbHitTrend.add(duration);
    }
  });

  sleep(0.5);

  // Scenario 4: Create Presensi (10% of requests - akan clear cache)
  if (Math.random() < 0.1) {
    group("Create Presensi", () => {
      const rfid = getRandomRFID();

      const payload = {
        rfid: rfid,
        timestamp: new Date().toISOString(),
        device_id: `ESP32_${Math.floor(Math.random() * 5) + 1}`,
      };

      const res = http.post(`${BASE_URL}/presensi`, JSON.stringify(payload), {
        headers,
      });

      const success = check(res, {
        "status is 200 or 400": (r) => r.status === 200 || r.status === 400,
        "has response": (r) => {
          const body = JSON.parse(r.body);
          return body.status;
        },
      });

      if (res.status === 200) {
        presensiCounter.add(1);
      }

      errorRate.add(!success);
    });

    sleep(0.5);
  }

  // Scenario 5: Device Stats (Should be cached)
  if (Math.random() < 0.2) {
    group("Get Device Stats", () => {
      const deviceId = `ESP32_${Math.floor(Math.random() * 5) + 1}`;
      const startTime = Date.now();

      const res = http.get(
        `${BASE_URL}/device-stats?device_id=${deviceId}&hours=24`,
        { headers }
      );

      const duration = Date.now() - startTime;

      const success = check(res, {
        "status is 200": (r) => r.status === 200,
        "has stats data": (r) => {
          const body = JSON.parse(r.body);
          return body.data && body.data.total_presensi !== undefined;
        },
        "response time < 300ms": (r) => r.timings.duration < 300,
      });

      errorRate.add(!success);

      if (duration < 50) {
        cacheHitTrend.add(duration);
      } else {
        dbHitTrend.add(duration);
      }
    });

    sleep(0.5);
  }

  // Scenario 6: Health Check (Should be heavily cached)
  // if (Math.random() < 0.3) {
  //   group("Health Check", () => {
  //     const startTime = Date.now();
  //     const res = http.get(`${BASE_URL}/health`, { headers });
  //     const duration = Date.now() - startTime;

  //     check(res, {
  //       "status is 200": (r) => r.status === 200,
  //       "api is healthy": (r) => {
  //         const body = JSON.parse(r.body);
  //         return body.status === "success";
  //       },
  //       "response time < 100ms": (r) => r.timings.duration < 100,
  //     });

  //     if (duration < 50) {
  //       cacheHitTrend.add(duration);
  //     }
  //   });
  // }

  sleep(1);
}

// Teardown: Jalankan sekali di akhir
export function teardown(data) {
  const duration = (Date.now() - data.startTime) / 1000;

  console.log("\n📊 ===============================================");
  console.log("🏁 Load Test Completed!");
  console.log(`⏱️  Total Duration: ${duration.toFixed(2)}s`);
  console.log("📊 ===============================================\n");

  // Final health check
  // const healthRes = http.get(`${BASE_URL}/health`);
  // console.log(
  //   `✅ Final Health Check: ${healthRes.status === 200 ? "PASSED" : "FAILED"}`
  // );
}

// Export fungsi untuk test scenarios khusus

// Test Scenario: Cache Performance (Compare with/without cache)
export function cachePerformanceTest() {
  const headers = getHeaders();
  const iterations = 100;

  console.log("🧪 Testing Cache Performance...\n");

  // Test 1: Jadwal Hari Ini (should hit cache after first request)
  console.log("📅 Testing Jadwal Hari Ini (100 requests)...");
  const jadwalTimes = [];

  for (let i = 0; i < iterations; i++) {
    const start = Date.now();
    http.get(`${BASE_URL}/jadwal-hari-ini`, { headers });
    jadwalTimes.push(Date.now() - start);
  }

  const avgJadwal = jadwalTimes.reduce((a, b) => a + b, 0) / iterations;
  const firstRequest = jadwalTimes[0];
  const avgCached =
    jadwalTimes.slice(1).reduce((a, b) => a + b, 0) / (iterations - 1);

  console.log(`   First Request (DB): ${firstRequest}ms`);
  console.log(`   Avg Cached Requests: ${avgCached.toFixed(2)}ms`);
  console.log(
    `   Speed Improvement: ${(firstRequest / avgCached).toFixed(2)}x faster\n`
  );

  // Test 2: RFID Validation
  console.log("🔐 Testing RFID Validation (100 requests on same RFID)...");
  const rfid = getRandomRFID();
  const rfidTimes = [];

  for (let i = 0; i < iterations; i++) {
    const start = Date.now();
    http.post(`${BASE_URL}/validate-rfid`, JSON.stringify({ rfid }), {
      headers,
    });
    rfidTimes.push(Date.now() - start);
  }

  const firstRfid = rfidTimes[0];
  const avgRfidCached =
    rfidTimes.slice(1).reduce((a, b) => a + b, 0) / (iterations - 1);

  console.log(`   First Request (DB): ${firstRfid}ms`);
  console.log(`   Avg Cached Requests: ${avgRfidCached.toFixed(2)}ms`);
  console.log(
    `   Speed Improvement: ${(firstRfid / avgRfidCached).toFixed(2)}x faster\n`
  );
}

// Test Scenario: Stress Test untuk Bulk Sync
export function bulkSyncStressTest() {
  const headers = getHeaders();

  console.log("💥 Stress Testing Bulk Sync...\n");

  // Create bulk data
  const bulkData = [];
  for (let i = 0; i < 50; i++) {
    bulkData.push({
      rfid: getRandomRFID(),
      timestamp: new Date().toISOString(),
      device_id: "ESP32_STRESS_TEST",
    });
  }

  const start = Date.now();
  const res = http.post(
    `${BASE_URL}/sync-bulk`,
    JSON.stringify({ data: bulkData }),
    { headers, timeout: "60s" }
  );
  const duration = Date.now() - start;

  console.log(`   Synced ${bulkData.length} records in ${duration}ms`);
  console.log(
    `   Avg per record: ${(duration / bulkData.length).toFixed(2)}ms`
  );
  console.log(`   Status: ${res.status}\n`);
}
