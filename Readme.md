Description
---

Unity Care Clinic V2 est une application backoffice développée en PHP orienté objet, destinée à gérer le parcours patient au sein d’une clinique médicale.
Elle permet la gestion des utilisateurs, des patients, des médecins, des départements, des rendez-vous et des prescriptions médicales, avec un système de rôles et de sécurité.

Fonctionnalités principales
---

Authentification sécurisée (Admin, Doctor, Patient)

Gestion des utilisateurs et contrôle d’accès par rôle (RBAC)

Gestion des départements, médecins et patients

Gestion des rendez-vous médicaux

Gestion des prescriptions et médicaments

Tableau de bord avec statistiques

Sécurisation contre XSS, CSRF et injections SQL

Architecture
---

Architecture PHP orientée objet (OOP)

Séparation claire des responsabilités (modèles, logique métier, vues)

Accès base de données sécurisé via PDO

Gestion centralisée des sessions et des rôles

Base de données
---

Base de données relationnelle MySQL

Tables principales : users, patients, doctors, departments, appointments, prescriptions, medications

Relations entre les entités via clés étrangères

Script SQL fourni pour la création de la base

Sécurité
---

Mots de passe hashés

Sessions PHP sécurisées

Vérification des rôles sur chaque page

Protection XSS et CSRF

Requêtes préparées pour toutes les opérations SQL


Auteur
---

Projet réalisé dans le cadre d’un travail individuel de développement backend PHP.