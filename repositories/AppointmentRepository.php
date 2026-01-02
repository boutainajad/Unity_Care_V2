<?php

class AppointmentRepository extends BaseRepository
{
    protected string $table = 'appointments';

    public function create(Appointment $appointment): bool
    {
        $sql = "INSERT INTO appointments 
                (doctor_id, patient_id, date, time, reason, status)
                VALUES
                (:doctor_id, :patient_id, :date, :time, :reason, :status)";

        return $this->pdo->prepare($sql)->execute([
            'doctor_id' => $appointment->getDoctorId(),
            'patient_id' => $appointment->getPatientId(),
            'date' => $appointment->getDate(),
            'time' => $appointment->getTime(),
            'reason' => $appointment->getReason(),
            'status' => $appointment->getStatus()
        ]);
    }

    public function findByDoctor(int $doctorId): array
    {
        $sql = "SELECT * FROM appointments WHERE doctor_id = :doctor_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPatient(int $patientId): array
    {
        $sql = "SELECT * FROM appointments WHERE patient_id = :patient_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
