<?php

class Prescription
{
    private int $id;
    private int $doctorId;
    private int $patientId;
    private int $medicationId;
    private string $instructions;
    private string $date;

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getDoctorId(): int { return $this->doctorId; }
    public function setDoctorId(int $doctorId): void { $this->doctorId = $doctorId; }

    public function getPatientId(): int { return $this->patientId; }
    public function setPatientId(int $patientId): void { $this->patientId = $patientId; }

    public function getMedicationId(): int { return $this->medicationId; }
    public function setMedicationId(int $medicationId): void { $this->medicationId = $medicationId; }

    public function getInstructions(): string { return $this->instructions; }
    public function setInstructions(string $instructions): void { $this->instructions = $instructions; }

    public function getDate(): string { return $this->date; }
    public function setDate(string $date): void { $this->date = $date; }
}
