<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class AnnouncementSeeder extends Seeder
{
    /**
     * Seed a few example announcements for the notification bell.
     */
    public function run(): void
    {
        $admin = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('email')
            ->first();

        if ($admin === null) {
            return;
        }

        $now = now();
        $rows = [];
        $samples = [
            1 => [
                'title' => 'Selamat datang di Rumitech',
                'body' => 'Gunakan menu di samping untuk mengelola stok, barang masuk, barang keluar, dan laporan gudang. Jika menemukan kendala, hubungi admin.',
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_ALL,
                'hours_ago' => 1,
            ],
            2 => [
                'title' => 'Jadwal stock opname gudang Jakarta',
                'body' => 'Stock opname akan dilaksanakan akhir pekan ini. Pastikan seluruh transaksi barang masuk dan keluar sudah dicatat sebelum proses penghitungan dimulai.',
                'type' => Announcement::TYPE_WARNING,
                'audience' => Announcement::AUDIENCE_ALL,
                'hours_ago' => 26,
            ],
            3 => [
                'title' => 'Alur penerimaan barang diperbarui',
                'body' => 'Penerimaan barang sekarang wajib melewati pemeriksaan kualitas sebelum ditempatkan ke rak. Gunakan menu Barang masuk untuk melihat detail alur terbaru.',
                'type' => Announcement::TYPE_SUCCESS,
                'audience' => Announcement::AUDIENCE_ALL,
                'hours_ago' => 72,
            ],
            4 => [
                'title' => 'Pengingat keamanan akun',
                'body' => 'Jangan bagikan kata sandi kepada siapa pun. Segera hubungi admin jika melihat aktivitas masuk yang tidak Anda kenali.',
                'type' => Announcement::TYPE_DANGER,
                'audience' => Announcement::AUDIENCE_ALL,
                'hours_ago' => 168,
            ],
            5 => [
                'title' => 'Akses laporan sudah aktif',
                'body' => 'Akun Anda sekarang dapat membuka menu laporan stok dan mutasi barang. Silakan gunakan filter tanggal untuk melihat data yang dibutuhkan.',
                'type' => Announcement::TYPE_SUCCESS,
                'audience' => Announcement::AUDIENCE_SELECTED,
                'hours_ago' => 2,
            ],
        ];

        foreach ($samples as $index => $sample) {
            $timestamp = $now->copy()->subHours($sample['hours_ago']);

            $rows[] = [
                'id' => $this->id($index),
                'title' => $sample['title'],
                'body' => $sample['body'],
                'type' => $sample['type'],
                'audience' => $sample['audience'],
                'created_by' => $admin->id,
                'created_at' => $timestamp->format('Y-m-d H:i:s'),
                'updated_at' => $timestamp->format('Y-m-d H:i:s'),
            ];
        }

        DB::table('announcements')->insertOrIgnore($rows);

        DB::table('announcement_recipients')->insertOrIgnore([
            'announcement_id' => $this->id(5),
            'user_id' => $admin->id,
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
    }

    private function id(int $index): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, "cms-announcement:{$index}")->toString();
    }
}
