<?php
require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../models/Medication.php';

class MedicationRepository extends BaseRepository
{
    protected string $table = 'medications';

    public function create(Medication $medication): bool
    {
        $sql = "INSERT INTO medications (name, dosage)
                VALUES (:name, :dosage)";

        return $this->pdo->prepare($sql)->execute([
            'name' => $medication->getName(),
            'dosage' => $medication->getDosage()
        ]);
    }

    public function findAllMedications(): array
    {
        return parent::findAll();
    }
}
