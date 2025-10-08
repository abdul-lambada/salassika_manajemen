# WhatsApp Automation for School Attendance System

## Overview
This document explains how to set up and use the WhatsApp automation features for the school attendance system. The automation system sends notifications to users, parents, and administrators based on attendance events.

## Features
- Automatic notifications for attendance events (present, late, absent, permission, sick)
- Daily attendance summary reports
- Weekend notification options
- Comprehensive logging of all automation events
- Admin interface for configuration and monitoring

## Setup Instructions

### 1. Database Schema
Run the SQL script located at `db/whatsapp_automation_schema.sql` to create the necessary tables:
- `whatsapp_automation_config` - Configuration settings
- `whatsapp_automation_logs` - Event logging

### 2. Configuration
Access the admin panel at `admin/whatsapp/automation_settings.php` to configure automation settings:
- Enable/disable automation
- Set notification preferences
- Configure timing for absence checks and daily summaries
- Set late arrival threshold

### 3. Cron Job Setup
Set up a cron job to run `cron/whatsapp_attendance_automation.php` every minute:
```
* * * * * /usr/bin/php /path/to/absensi_sekolah/cron/whatsapp_attendance_automation.php
```

## Usage

### Manual Testing
Use the test buttons in the admin automation settings page:
- Test Daily Summary
- Test Absent Student Check

### Monitoring
View automation logs at `admin/whatsapp/automation_logs.php` to monitor system performance and troubleshoot issues.

## Template Variables

### User Confirmation Templates
- `attendance_confirmation`: {{nama}}, {{tanggal}}, {{waktu}}, {{status}}
- `attendance_late`: {{nama}}, {{tanggal}}, {{waktu}}, {{menit_terlambat}}
- `attendance_permission_confirm`: {{nama}}, {{tanggal}}, {{status}}
- `attendance_sick_confirm`: {{nama}}, {{tanggal}}, {{status}}

### Parent Notification Templates
- `attendance_present_parent`: {{nama_anak}}, {{kelas}}, {{tanggal}}, {{waktu}}, {{status}}
- `attendance_late_parent`: {{nama_anak}}, {{kelas}}, {{tanggal}}, {{waktu}}, {{menit_terlambat}}
- `attendance_absent_parent`: {{nama_anak}}, {{kelas}}, {{tanggal}}, {{status}}
- `attendance_permission_parent`: {{nama_anak}}, {{kelas}}, {{tanggal}}, {{status}}
- `attendance_sick_parent`: {{nama_anak}}, {{kelas}}, {{tanggal}}, {{status}}

### Admin Notification Templates
- `late_alert`: {{nama}}, {{user_type}}, {{kelas}}, {{tanggal}}, {{waktu}}, {{menit_terlambat}}
- `absence_alert`: {{nama}}, {{user_type}}, {{kelas}}, {{tanggal}}, {{status}}
- `daily_attendance_summary`: {{tanggal}}, {{hadir}}, {{terlambat}}, {{tidak_hadir}}, {{izin}}, {{sakit}}, {{total}}

## Integration with Attendance System

To integrate with the existing attendance processing system, call the automation functions from your attendance processing code:

```php
// Include the automation class
require_once __DIR__ . '/includes/attendance_whatsapp_automation.php';

// Send notification for an attendance event
$automation = new AttendanceWhatsAppAutomation($conn);
$automation->processAttendanceNotifications([
    'user_id' => 123,
    'status_kehadiran' => 'hadir', // or 'terlambat', 'tidak_hadir', 'izin', 'sakit'
    'timestamp' => '2023-05-15 08:15:00',
    'user_type' => 'siswa' // or 'guru'
]);
```

## Troubleshooting

### No Notifications Sent
1. Check if automation is enabled in settings
2. Verify Fonnte API key in WhatsApp configuration
3. Check automation logs for error messages
4. Ensure phone numbers are properly formatted in user records

### Cron Job Issues
1. Verify cron job is running with proper permissions
2. Check PHP CLI is installed and accessible
3. Ensure all file paths in cron script are correct
4. Check system logs for cron execution errors

## Customization

### Adding New Notification Types
1. Create new message templates in the WhatsApp templates admin
2. Add new handler methods in `AttendanceWhatsAppAutomation` class
3. Update the `processAttendanceNotifications` method to handle new statuses

### Modifying Notification Logic
Edit the `AttendanceWhatsAppAutomation` class methods to customize when and how notifications are sent.
