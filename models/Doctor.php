<?php

class Doctor extends User
{
    private int $departmentId;
    private string $speciality;

    public function __construct()
    {
        $this->role = 'DOCTOR';
    }

    public function getDepartmentId(): int { return $this->departmentId; }
    public function setDepartmentId(int $id): void { $this->departmentId = $id; }

    public function getSpeciality(): string { return $this->speciality; }
    public function setSpeciality(string $speciality): void { $this->speciality = $speciality; }
}
