<?php

namespace App\Models;

use App\Support\StudentWorkflow;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'id';

    // Legacy table only has limited timestamps.
    public $timestamps = false;

    protected $fillable = [
        'registration_number','name','dob','phone','address','status','created_at',
        'submitted_at','reviewed_at','reviewed_by_admin_id','result_note','announcement_published_at','last_status_note',
        'kk_file','ktp_father','ktp_mother',
        'guardian_name','guardian_phone','guardian_relation','ktp_guardian','kk_guardian',
        'father_name','father_phone','father_job',
        'mother_name','mother_phone','mother_job',
        'akta_lahir','nilai_rapor',
        'father_email','father_income','father_birthplace','father_dob',
        'mother_email','mother_income','mother_birthplace','mother_dob',
        'guardian_birthplace','guardian_dob','guardian_email','guardian_income',
        'student_hobby','goal','motivation','birthplace','gender',
        'religion_child','father_religion','mother_religion','guardian_religion',
        'user_id','rejection_reason'
    ];

    protected $casts = [
        'dob' => 'date',
        'father_dob' => 'date',
        'mother_dob' => 'date',
        'guardian_dob' => 'date',
        'created_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'announcement_published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(LegacyUser::class, 'user_id', 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'student_id', 'id');
    }

    public function statusHistories()
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'student_id', 'id')->orderBy('created_at');
    }

    public function interview()
    {
        return $this->hasOne(Interview::class, 'student_id', 'id')->latestOfMany('scheduled_at');
    }

    public function applicationV3()
    {
        return $this->hasOne(StudentApplication::class, 'legacy_student_id', 'id');
    }

    public function getStatusNormalizedAttribute(): string
    {
        return StudentWorkflow::normalizeStatus($this->status);
    }

    public function getStatusLabelAttribute(): string
    {
        return StudentWorkflow::label($this->status_normalized);
    }

    public function getProgressPercentAttribute(): int
    {
        return StudentWorkflow::progressPercent($this->status_normalized);
    }

    public function getRequiredDocumentMapAttribute(): array
    {
        return [
            'kk_file' => 'Kartu Keluarga',
            'akta_lahir' => 'Akta Lahir',
            'nilai_rapor' => 'Nilai Rapor',
            'ktp_father' => 'KTP Ayah',
            'ktp_mother' => 'KTP Ibu',
        ];
    }

    public function getMissingDocumentsAttribute(): array
    {
        $missing = [];
        foreach ($this->required_document_map as $column => $label) {
            if (empty($this->{$column})) {
                $missing[$column] = $label;
            }
        }

        return $missing;
    }

    public function getIsDocumentsCompleteAttribute(): bool
    {
        return count($this->missing_documents) === 0;
    }
}
