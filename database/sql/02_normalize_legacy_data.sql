-- Menormalkan data legacy yang kosong/tidak konsisten agar lebih aman dipakai di Laravel.

UPDATE `admin`
SET `role` = 'panitia'
WHERE `role` = '' OR `role` IS NULL;

UPDATE `students`
SET `status` = 'pending'
WHERE `status` = '' OR `status` IS NULL;
