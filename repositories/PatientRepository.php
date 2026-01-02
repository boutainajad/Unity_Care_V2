<?php

class PatientRepository extends BaseRepository
{
    protected string $table = 'patients';

    public function create(Patient $patient): bool
    {
        $sql = "INSERT INTO patients 
                (first_name, last_name, email, username, password, phone) 
                VALUES 
                (:first_name, :last_name, :email, :username, :password, :phone)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'first_name' => $patient->getFirstName(),
            'last_name'  => $patient->getLastName(),
            'email'      => $patient->getEmail(),
            'username'   => $patient->getUsername(),
            'password'   => $patient->getPassword(),
            'phone'      => $patient->getPhone()
        ]);
    }

    public function update(Patient $patient): bool
    {
        $sql = "UPDATE patients SET
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'first_name' => $patient->getFirstName(),
            'last_name'  => $patient->getLastName(),
            'phone'      => $patient->getPhone(),
            'id'         => $patient->getId()
        ]);
    }

    public function findPatientById(int $id): ?Patient
    {
        $data = parent::findById($id);

        if (!$data) {
            return null;
        }

        $patient = new Patient();
        $patient->setId($data['id']);
        $patient->setFirstName($data['first_name']);
        $patient->setLastName($data['last_name']);
        $patient->setEmail($data['email']);
        $patient->setUsername($data['username']);
        $patient->setPhone($data['phone']);

        return $patient;
    }
}
