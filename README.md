# Perceptor
BearTrax Perceptor — camera capture system with a WordPress dashboard, live preview, and Raspberry Pi capture worker.

## Overview
Perceptor is a two-part system:

1. **WordPress Plugin** (`wp-plugin/`)
   - Adds a Perceptor menu in the WordPress admin.
   - Provides dashboard, live preview, and settings pages.
   - Handles uploads and manages the job queue.

2. **Pi Server** (`pi-server/`)
   - Runs on a Raspberry Pi near your cameras.
   - Polls WordPress for capture jobs.
   - Uses `ffmpeg` to record from RTSP cameras.
   - Uploads captured files back to WordPress.

## Repository Structure
perceptor/
├── wp-plugin/        # WordPress plugin code (install here: wp-content/plugins/perceptor)
│   ├── perceptor.php
│   ├── dashboard.php
│   ├── preview.php
│   ├── settings.php
│   ├── ...
│
├── pi-server/        # Raspberry Pi capture worker
│   ├── bin/
│   │   ├── worker.php
│   │   └── lib.php
│   ├── config/
│   │   └── cameras.json
│   └── captures/     # where recordings are saved before upload
│
└── README.md         # this file

## WordPress Plugin Setup
1. Copy the `wp-plugin/` folder into your WordPress installation:  
   `wp-content/plugins/perceptor`
2. Activate **Perceptor** in the WordPress admin → Plugins.
3. Configure your cameras under **Settings → Perceptor**.

## Pi Server Setup
1. Copy the `pi-server/` directory to your Raspberry Pi. Example:  
   `/var/www/perceptor/pi-server`
2. Install dependencies on the Pi:  
   `sudo apt update && sudo apt install ffmpeg php-cli curl`
3. Create `/etc/perceptor.env` with your WordPress endpoint and secret:  
   WP_ENDPOINT=https://yourdomain.com/wp-json/perceptor/v1/upload  
   WP_SECRET=your-secret-key
4. Configure your cameras in:  
   `pi-server/config/cameras.json`
5. Run the worker:  
   `php bin/worker.php`  
   (Optionally, set this up as a systemd service to run at boot.)

## Live Preview
- The **Live Preview** tab in WordPress allows you to check the RTSP streams before capturing.  
- Camera names come from the settings you configure in WordPress.

## Development Notes
- WordPress REST endpoints: `wp-plugin/preview-api.php` and `wp-plugin/upload.php`  
- Capture worker logs: `pi-server/captures/ffmpeg.log`  
- Job queue: `wp-plugin/queue.php`  

## License
MIT (or whichever license you prefer)
