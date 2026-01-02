<?php

class Admin extends User
{
    public function __construct()
    {
        $this->role = 'ADMIN';
    }
}
