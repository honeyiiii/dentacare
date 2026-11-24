<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Encryption\DecryptException;

return new class extends Migration
{
    /** Fields stored as encrypted strings; array fields retain their JSON payload inside encryption. */
    private array $encryptedFields = [
        'medical_histories' => ['details', 'alert_note'],
        'dental_histories' => ['details', 'alert_note'],
        'tooth_charts' => ['condition'],
        'diagnoses' => ['diagnosis', 'notes'],
        'treatments' => ['procedure'],
        'progress_notes' => ['note'],
        'predictions' => ['input_data', 'result', 'risk_scores'],
        'prediction_decisions' => ['final_condition', 'note'],
    ];

    private array $arrayFields = ['input_data', 'risk_scores'];

    public function up(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->text('alert_note')->nullable()->change();
        });
        Schema::table('dental_histories', function (Blueprint $table) {
            $table->text('alert_note')->nullable()->change();
        });
        Schema::table('tooth_charts', function (Blueprint $table) {
            $table->text('condition')->change();
        });
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->text('diagnosis')->change();
        });
        Schema::table('treatments', function (Blueprint $table) {
            $table->text('procedure')->change();
        });
        Schema::table('predictions', function (Blueprint $table) {
            $table->text('result')->change();
            $table->text('risk_scores')->nullable()->change();
        });
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->text('final_condition')->nullable()->change();
        });

        $this->transformValues(encrypt: true);
    }

    public function down(): void
    {
        $this->transformValues(encrypt: false);

        Schema::table('medical_histories', function (Blueprint $table) {
            $table->text('alert_note')->nullable()->change();
        });
        Schema::table('dental_histories', function (Blueprint $table) {
            $table->text('alert_note')->nullable()->change();
        });
        Schema::table('tooth_charts', function (Blueprint $table) {
            $table->string('condition')->change();
        });
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('diagnosis')->change();
        });
        Schema::table('treatments', function (Blueprint $table) {
            $table->string('procedure')->change();
        });
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('result')->change();
            $table->json('risk_scores')->nullable()->change();
        });
        Schema::table('prediction_decisions', function (Blueprint $table) {
            $table->string('final_condition', 60)->nullable()->change();
        });
    }

    private function transformValues(bool $encrypt): void
    {
        foreach ($this->encryptedFields as $table => $fields) {
            DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $fields, $encrypt): void {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($fields as $field) {
                        $value = $row->{$field};
                        if ($value === null) {
                            continue;
                        }

                        if ($encrypt) {
                            try {
                                Crypt::decryptString($value);
                                continue; // Already encrypted by a previous deployment.
                            } catch (DecryptException) {
                                $plaintext = $value;
                            }

                            if (in_array($field, $this->arrayFields, true)) {
                                $decoded = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
                                $plaintext = json_encode($decoded, JSON_THROW_ON_ERROR);
                            }

                            $updates[$field] = Crypt::encryptString($plaintext);
                        } else {
                            try {
                                $updates[$field] = Crypt::decryptString($value);
                            } catch (DecryptException) {
                                continue; // Leave values created outside the encrypted cast untouched.
                            }
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }
};
