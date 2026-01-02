<?php

class Patient extends User
{
    private string $firstName;
    private string $lastName;
    private ?string $phone = null;

    public function __construct()
    {
        $this->role = 'PATIENT';
    }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }
}
