<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemConfigController extends Controller
{
    public function index()
    {
        $config = [
            'school_name' => config('app.school_name', 'My School'),
            'school_address' => config('app.school_address', ''),
            'school_phone' => config('app.school_phone', ''),
            'school_email' => config('app.school_email', ''),
            'school_logo' => config('app.school_logo', ''),
            'academic_year_start_month' => config('app.academic_year_start_month', 4),
            'attendance_cutoff_time' => config('app.attendance_cutoff_time', '09:00'),
            'enable_notifications' => config('app.enable_notifications', true),
            'enable_sms' => config('app.enable_sms', false),
            'enable_email' => config('app.enable_email', true),
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => config('app.date_format', 'Y-m-d'),
            'time_format' => config('app.time_format', 'H:i'),
            'currency' => config('app.currency', 'USD'),
            'currency_symbol' => config('app.currency_symbol', '$'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'app_version' => config('app.version', '1.0.0'),
            'last_backup' => Cache::get('last_backup', null),
            'storage_used' => $this->getStorageUsed(),
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'school_address' => 'nullable|string',
            'school_phone' => 'nullable|string|max:20',
            'school_email' => 'nullable|email|max:255',
            'academic_year_start_month' => 'nullable|integer|between:1,12',
            'attendance_cutoff_time' => 'nullable|string',
            'enable_notifications' => 'boolean',
            'enable_sms' => 'boolean',
            'enable_email' => 'boolean',
            'timezone' => 'nullable|timezone',
            'date_format' => 'nullable|string',
            'time_format' => 'nullable|string',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:5',
        ]);

        // Save to .env or database
        $this->saveConfig($request->all());

        // Clear config cache
        Cache::forget('app_config');

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $request->all()
        ]);
    }

    private function getStorageUsed()
    {
        $path = storage_path();
        $size = 0;
        
        foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->folderSize($each);
        }
        
        return $this->formatBytes($size);
    }

    private function folderSize($path)
    {
        $size = 0;
        foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->folderSize($each);
        }
        return $size;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function saveConfig($config)
    {
        // This is a simplified version - in production you'd want to save to database
        // For now, we'll just cache it
        Cache::forever('app_config', $config);
    }
}