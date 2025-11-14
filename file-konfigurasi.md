# Optimal settings

php artisan queue:work \
 --queue=whatsapp \
 --sleep=3 \
 --tries=3 \
 --max-time=3600 \
 --timeout=60 \
 --memory=128
