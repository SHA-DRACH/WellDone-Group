<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The employee record — the centre of the HR module.
 *
 * Separate from `users` on purpose. A user account is a way to sign in; an
 * employee is a person the company employs. Not every employee needs a login
 * (a driver or plant operator may never use the system), and not every user
 * is an employee (clients register on the public site). The optional
 * `user_id` links the two when a member of staff does need portal access.
 *
 * Personal identifiers — national ID, passport, bank details — are the most
 * sensitive data the application holds. They are confined to this table and
 * `employee_bank_details` so that access can be reasoned about in one place,
 * and are excluded from the model's default serialisation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Human-readable staff number, e.g. WDG-EMP-0001.
            $table->string('employee_number', 40)->unique();

            // Portal login, when this employee has one.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            // The application this person was hired from, where applicable.
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->nullOnDelete();

            // ------------------------------------------------ Personal
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('photo')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'undisclosed'])->default('undisclosed');
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'undisclosed'])
                ->default('undisclosed');
            $table->string('blood_group', 5)->nullable();

            // ------------------------------------------------ Contact
            $table->string('email')->nullable()->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->text('residential_address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Liberia');

            // ------------------------------------------------ Employment
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();

            // Self-referencing: who this person reports to.
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->date('date_hired')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('contract_expiry')->nullable();
            $table->enum('employment_status', [
                'probation', 'active', 'suspended', 'on_leave',
                'resigned', 'terminated', 'retired',
            ])->default('probation');
            $table->date('exit_date')->nullable();
            $table->string('work_location')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'employment_status']);
            $table->index(['division_id', 'is_active']);
            $table->index('employment_status');
            $table->index(['last_name', 'first_name']);
        });

        // The department head could not be constrained until employees existed.
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone');
            $table->string('alternative_phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_primary']);
        });

        /*
         * Payroll groundwork. Nothing calculates pay yet — these fields exist
         * so that when Payroll is built it has somewhere to read from without
         * a schema migration on live data.
         */
        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('pay_frequency', ['monthly', 'fortnightly', 'weekly', 'daily'])->default('monthly');
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('social_security_number')->nullable();
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->timestamps();

            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_employee_id']);
        });

        Schema::dropIfExists('employee_bank_details');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employees');
    }
};
