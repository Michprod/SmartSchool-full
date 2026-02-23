import React from 'react';
import type { Student } from '../types';
import './StudentDetails.css';

interface StudentDetailsProps {
  student: Student;
  onClose: () => void;
}

const StudentDetails: React.FC<StudentDetailsProps> = ({ student, onClose }) => {
  return (
    <div className="student-details">
      <div className="details-header">
        <div className="student-header-info">
          <div className="student-photo-large">
            {student.photo ? (
              <img src={student.photo} alt={`${student.firstName} ${student.lastName}`} />
            ) : (
              <div className="photo-placeholder">
                <span>{student.firstName[0]}{student.lastName[0]}</span>
              </div>
            )}
          </div>
          <div>
            <h2>{student.firstName} {student.lastName}</h2>
            <p className="student-matricule">Matricule: {student.matricule}</p>
            <span className={`status-badge ${student.status}`}>
              {student.status === 'active' ? 'Actif' : student.status === 'suspended' ? 'Suspendu' : 'Inactif'}
            </span>
          </div>
        </div>
        <button className="close-btn" onClick={onClose}>×</button>
      </div>

      <div className="details-content">
        {/* Personal Information */}
        <div className="details-section">
          <h3>📋 Informations Personnelles</h3>
          <div className="info-grid">
            <div className="info-item">
              <label>Nom complet</label>
              <p>{student.firstName} {student.lastName}</p>
            </div>
            <div className="info-item">
              <label>Date de naissance</label>
              <p>{new Date(student.dateOfBirth).toLocaleDateString('fr-FR')}</p>
            </div>
            <div className="info-item">
              <label>Genre</label>
              <p>{student.gender === 'M' ? 'Masculin' : 'Féminin'}</p>
            </div>
            <div className="info-item">
              <label>Lieu de naissance</label>
              <p>{student.placeOfBirth || 'Non renseigné'}</p>
            </div>
            <div className="info-item">
              <label>Nationalité</label>
              <p>{student.nationality || 'Congolaise (RDC)'}</p>
            </div>
            <div className="info-item">
              <label>Groupe sanguin</label>
              <p>{student.bloodGroup || 'Non renseigné'}</p>
            </div>
          </div>
        </div>

        {/* Contact Information */}
        <div className="details-section">
          <h3>📞 Coordonnées</h3>
          <div className="info-grid">
            <div className="info-item">
              <label>Adresse</label>
              <p>{student.address || 'Non renseignée'}</p>
            </div>
            <div className="info-item">
              <label>Ville</label>
              <p>{student.city || 'Non renseignée'}</p>
            </div>
            <div className="info-item">
              <label>Province</label>
              <p>{student.province || 'Non renseignée'}</p>
            </div>
            <div className="info-item">
              <label>Téléphone</label>
              <p>{student.phone || 'Non renseigné'}</p>
            </div>
            <div className="info-item">
              <label>Email</label>
              <p>{student.email || 'Non renseigné'}</p>
            </div>
          </div>
        </div>

        {/* Guardian Information */}
        <div className="details-section">
          <h3>👨‍👩‍👧 Tuteur / Parent</h3>
          <div className="info-grid">
            <div className="info-item">
              <label>Nom du tuteur</label>
              <p>{student.guardianName || 'Non renseigné'}</p>
            </div>
            <div className="info-item">
              <label>Relation</label>
              <p>{student.guardianRelation || 'Non renseignée'}</p>
            </div>
            <div className="info-item">
              <label>Téléphone du tuteur</label>
              <p>{student.guardianPhone || 'Non renseigné'}</p>
            </div>
            <div className="info-item">
              <label>Email du tuteur</label>
              <p>{student.guardianEmail || 'Non renseigné'}</p>
            </div>
          </div>
        </div>

        {/* Academic Information */}
        <div className="details-section">
          <h3>📚 Informations Académiques</h3>
          <div className="info-grid">
            <div className="info-item">
              <label>Classe</label>
              <p>{student.class}</p>
            </div>
            <div className="info-item">
              <label>Année académique</label>
              <p>{student.academicYear || '2024-2025'}</p>
            </div>
            <div className="info-item">
              <label>Date d'inscription</label>
              <p>{new Date(student.enrollmentDate).toLocaleDateString('fr-FR')}</p>
            </div>
            <div className="info-item">
              <label>Statut académique</label>
              <p>{student.academicStatus || 'Régulier'}</p>
            </div>
            {student.previousSchool && (
              <div className="info-item full-width">
                <label>École précédente</label>
                <p>{student.previousSchool}</p>
              </div>
            )}
          </div>
        </div>

        {/* Health Information */}
        {(student.allergies || student.medicalConditions || student.emergencyContact) && (
          <div className="details-section">
            <h3>🏥 Informations Médicales</h3>
            <div className="info-grid">
              {student.allergies && (
                <div className="info-item full-width">
                  <label>Allergies</label>
                  <p>{student.allergies}</p>
                </div>
              )}
              {student.medicalConditions && (
                <div className="info-item full-width">
                  <label>Conditions médicales</label>
                  <p>{student.medicalConditions}</p>
                </div>
              )}
              {student.emergencyContact && (
                <div className="info-item">
                  <label>Contact d'urgence</label>
                  <p>{student.emergencyContact}</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Actions */}
        <div className="details-actions">
          <button className="btn btn-outline" onClick={onClose}>
            Fermer
          </button>
          <button className="btn btn-primary">
            <span>✏️</span>
            Modifier
          </button>
          <button className="btn btn-secondary">
            <span>💰</span>
            Voir Paiements
          </button>
          <button className="btn btn-secondary">
            <span>📞</span>
            Contacter Parent
          </button>
        </div>
      </div>
    </div>
  );
};

export default StudentDetails;