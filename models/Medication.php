<?php

class Medication
{
    private int $id;
    private string $name;
    private string $dosage;

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDosage(): string { return $this->dosage; }
    public function setDosage(string $dosage): void { $this->dosage = $dosage; }
}
