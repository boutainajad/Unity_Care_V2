<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../models/Doctor.php';

class DoctorRepository extends BaseRepository
{
    protected string $table = 'doctors';

    public function findDoctorById(int $id): ?Doctor
    {
        $data = parent::findById($id);

        if (!$data) {
            return null;
        }

        $doctor = new Doctor();
        $doctor->setId($data['id']);
        $doctor->setEmail($data['email']);
        $doctor->setUsername($data['username']);
        $doctor->setSpeciality($data['speciality']);
        $doctor->setDepartmentId($data['department_id']);

        return $doctor;
    }
}
