<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow w-[350px] mx-auto">
            @if ($kartuUrl)
            <img src="{{ $kartuUrl }}"
                alt="Kartu {{ $siswa->user->name ?? $siswa->nama ?? 'Siswa' }}"
                class="w-full rounded-lg border object-contain">
            @else
            <p class="text-center text-gray-500">
                Kartu tidak ditemukan.
            </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>