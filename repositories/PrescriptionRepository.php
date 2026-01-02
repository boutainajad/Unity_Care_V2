<?php
require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../models/Prescription.php';

class PrescriptionRepository extends BaseRepository
{
    protected string $table = 'prescriptions';

    public function create(Prescription $prescription): bool
    {
        $sql = "INSERT INTO prescriptions
                (doctor_id, patient_id, medication_id, instructions, date)
                VALUES
                (:doctor_id, :patient_id, :medication_id, :instructions, :date)";

        return $this->pdo->prepare($sql)->execute([
            'doctor_id' => $prescription->getDoctorId(),
            'patient_id' => $prescription->getPatientId(),
            'medication_id' => $prescription->getMedicationId(),
            'instructions' => $prescription->getInstructions(),
            'date' => $prescription->getDate()
        ]);
    }

    public function findByPatient(int $patientId): array
    {
        $sql = "SELECT * FROM prescriptions WHERE patient_id = :patient_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDoctor(int $doctorId): array
    {
        $sql = "SELECT * FROM prescriptions WHERE doctor_id = :doctor_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
