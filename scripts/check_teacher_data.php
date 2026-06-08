<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

foreach (['flux.teacher@smartschool.cd', 'prof.kabongo@smartschool.cd'] as $email) {
    $u = User::where('email', $email)->first();
    if (! $u) {
        echo "$email: absent\n";
        continue;
    }
    $cs = ClassSubject::where('teacher_id', $u->id)->count();
    $principal = SchoolClass::where('teacher_id', $u->id)->count();
    echo "$email: class_subjects=$cs, titulaire=$principal\n";
}

$fluxClass = SchoolClass::where('name', 'like', '%FLUX%')->first();
if ($fluxClass) {
    $students = Student::where('class_id', $fluxClass->id)->count();
    echo "Classe FLUX: {$fluxClass->display_name}, élèves=$students\n";
} else {
    echo "Classe FLUX: absente (lancer php scripts/run_local_workflow.php)\n";
}
