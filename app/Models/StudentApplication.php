<?php

namespace App\Models;

use App\Support\StudentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentApplication extends Model
{
    use SoftDeletes;

    protected $table = 'student_applications';

    protected $fillable = [
        'legacy_student_id','account_id','registration_no','academic_year','current_status','submitted_at','reviewed_at',
        'reviewed_by_account_id','rejection_reason','result_notes','announcement_published_at','last_status_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'announcement_published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function account() { return $this->belongsTo(Account::class, 'account_id'); }
    public function profile() { return $this->hasOne(StudentProfile::class, 'application_id'); }
    public function guardians() { return $this->hasMany(ApplicationGuardian::class, 'application_id'); }
    public function documents() { return $this->hasMany(ApplicationDocument::class, 'application_id'); }
    public function interviews() { return $this->hasMany(ApplicationInterview::class, 'application_id'); }
    public function result() { return $this->hasOne(ApplicationResult::class, 'application_id'); }
    public function statusHistories() { return $this->hasMany(ApplicationStatusHistory::class, 'application_id')->orderBy('created_at'); }
    public function interview() { return $this->hasOne(ApplicationInterview::class, 'application_id')->latestOfMany('scheduled_at'); }

    public function getRegistrationNumberAttribute(): ?string
    {
        return $this->registration_no;
    }

    public function getStatusNormalizedAttribute(): string
    {
        return StudentWorkflow::normalizeStatus($this->current_status);
    }

    public function getStatusLabelAttribute(): string
    {
        return StudentWorkflow::label($this->current_status);
    }

    public function getProgressPercentAttribute(): int
    {
        return StudentWorkflow::progressPercent($this->current_status);
    }

    public function getNameAttribute(): ?string { return $this->profile?->full_name; }
    public function getBirthplaceAttribute(): ?string { return $this->profile?->birth_place; }
    public function getDobAttribute() { return $this->profile?->birth_date; }
    public function getPhoneAttribute(): ?string { return $this->profile?->phone ?: $this->account?->phone; }
    public function getAddressAttribute(): ?string { return $this->profile?->address; }
    public function getMotivationAttribute(): ?string { return $this->profile?->motivation; }
    public function getGenderAttribute(): ?string { return $this->profile?->gender; }
    public function getReligionChildAttribute(): ?string { return $this->profile?->religion; }
    public function getStudentHobbyAttribute(): ?string { return $this->profile?->hobby; }
    public function getGoalAttribute(): ?string { return $this->profile?->goal; }
    public function getResultNoteAttribute(): ?string { return $this->result_notes; }

    protected function guardian(string $type): ?ApplicationGuardian
    {
        return $this->relationLoaded('guardians')
            ? $this->guardians->firstWhere('guardian_type', $type)
            : $this->guardians()->where('guardian_type', $type)->first();
    }

    public function getFatherNameAttribute(): ?string { return $this->guardian('father')?->full_name; }
    public function getFatherPhoneAttribute(): ?string { return $this->guardian('father')?->phone; }
    public function getFatherEmailAttribute(): ?string { return $this->guardian('father')?->email; }
    public function getFatherJobAttribute(): ?string { return $this->guardian('father')?->occupation; }
    public function getFatherIncomeAttribute(): ?string { return $this->guardian('father')?->income_range; }
    public function getFatherBirthplaceAttribute(): ?string { return $this->guardian('father')?->birth_place; }
    public function getFatherDobAttribute() { return $this->guardian('father')?->birth_date; }
    public function getFatherReligionAttribute(): ?string { return $this->guardian('father')?->religion; }

    public function getMotherNameAttribute(): ?string { return $this->guardian('mother')?->full_name; }
    public function getMotherPhoneAttribute(): ?string { return $this->guardian('mother')?->phone; }
    public function getMotherEmailAttribute(): ?string { return $this->guardian('mother')?->email; }
    public function getMotherJobAttribute(): ?string { return $this->guardian('mother')?->occupation; }
    public function getMotherIncomeAttribute(): ?string { return $this->guardian('mother')?->income_range; }
    public function getMotherBirthplaceAttribute(): ?string { return $this->guardian('mother')?->birth_place; }
    public function getMotherDobAttribute() { return $this->guardian('mother')?->birth_date; }
    public function getMotherReligionAttribute(): ?string { return $this->guardian('mother')?->religion; }

    public function getGuardianNameAttribute(): ?string { return $this->guardian('guardian')?->full_name; }
    public function getGuardianPhoneAttribute(): ?string { return $this->guardian('guardian')?->phone; }
    public function getGuardianEmailAttribute(): ?string { return $this->guardian('guardian')?->email; }
    public function getGuardianIncomeAttribute(): ?string { return $this->guardian('guardian')?->income_range; }
    public function getGuardianBirthplaceAttribute(): ?string { return $this->guardian('guardian')?->birth_place; }
    public function getGuardianDobAttribute() { return $this->guardian('guardian')?->birth_date; }
    public function getGuardianReligionAttribute(): ?string { return $this->guardian('guardian')?->religion; }
    public function getGuardianRelationAttribute(): ?string { return $this->guardian('guardian')?->relationship_to_student; }

    protected function documentPath(string $type): ?string
    {
        $doc = $this->relationLoaded('documents')
            ? $this->documents->firstWhere('document_type', $type)
            : $this->documents()->where('document_type', $type)->first();
        return $doc?->file_path;
    }

    public function getKkFileAttribute(): ?string { return $this->documentPath('kk'); }
    public function getAktaLahirAttribute(): ?string { return $this->documentPath('akta'); }
    public function getNilaiRaporAttribute(): ?string { return $this->documentPath('rapor'); }
    public function getKtpFatherAttribute(): ?string { return $this->documentPath('ktp_father'); }
    public function getKtpMotherAttribute(): ?string { return $this->documentPath('ktp_mother'); }
    public function getKtpGuardianAttribute(): ?string { return $this->documentPath('ktp_guardian'); }
    public function getKkGuardianAttribute(): ?string { return $this->documentPath('kk_guardian'); }

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
