import React, { useState } from 'react';
import type { FormEvent } from 'react';
import PhotoUpload from './PhotoUpload';
import './StudentForm.css';

export interface StudentFormData {
  id?: string;
  matricule: string;
  firstName: string;
  lastName: string;
  dateOfBirth: string;
  gender: 'M' | 'F';
  placeOfBirth: string;
  nationality: string;
  bloodGroup?: string;
  photo?: string;
  
  // Contact Information
  phone?: string;
  email?: string;
  address: string;
  city: string;
  province: string;
  
  // Guardian Information
  guardianName: string;
  guardianRelation: string;
  guardianPhone: string;
  guardianEmail?: string;
  guardianAddress?: string;
  guardianOccupation?: string;
  
  // Academic Information
  class: string;
  section?: string;
  academicYear: string;
  previousSchool?: string;
  admissionDate: string;
  
  // Health Information
  allergies?: string;
  medicalConditions?: string;
  emergencyContact: string;
  emergencyPhone: string;
  
  // Documents
  hasbirthCertificate: boolean;
  hasVaccinationCard: boolean;
  hasReportCard: boolean;
  hasPhoto: boolean;
  
  // Financial
  tuitionStatus: 'paid' | 'partial' | 'unpaid';
  scholarshipStatus?: 'none' | 'partial' | 'full';
  
  // Status
  status: 'active' | 'inactive' | 'graduated' | 'transferred' | 'expelled';
  notes?: string;
}

interface StudentFormProps {
  initialData?: StudentFormData;
  classes?: {id: string | number, name: string}[];
  onSubmit: (data: StudentFormData) => void;
  onCancel: () => void;
  mode?: 'create' | 'edit';
}

const StudentForm: React.FC<StudentFormProps> = ({
  initialData,
  classes,
  onSubmit,
  onCancel,
  mode = 'create'
}) => {
  const [formData, setFormData] = useState<StudentFormData>(initialData || {
    matricule: '',
    firstName: '',
    lastName: '',
    dateOfBirth: '',
    gender: 'M',
    placeOfBirth: '',
    nationality: 'Congolaise (RDC)',
    address: '',
    city: '',
    province: '',
    guardianName: '',
    guardianRelation: 'Père',
    guardianPhone: '',
    class: '',
    academicYear: new Date().getFullYear().toString(),
    admissionDate: new Date().toISOString().split('T')[0],
    emergencyContact: '',
    emergencyPhone: '',
    hasbirthCertificate: false,
    hasVaccinationCard: false,
    hasReportCard: false,
    hasPhoto: false,
    tuitionStatus: 'unpaid',
    status: 'active'
  });

  const [errors, setErrors] = useState<Partial<Record<keyof StudentFormData, string>>>({});
  const [currentStep, setCurrentStep] = useState(1);
  const [photoFile, setPhotoFile] = useState<File | null>(null);

  const provinces = [
    'Kinshasa', 'Kongo-Central', 'Kwango', 'Kwilu', 'Mai-Ndombe',
    'Kasaï', 'Kasaï-Central', 'Kasaï-Oriental', 'Lomami', 'Sankuru',
    'Maniema', 'Sud-Kivu', 'Nord-Kivu', 'Ituri', 'Haut-Uele', 'Tshopo', 'Bas-Uele',
    'Nord-Ubangi', 'Mongala', 'Sud-Ubangi', 'Équateur', 'Tshuapa',
    'Tanganyika', 'Haut-Lomami', 'Lualaba', 'Haut-Katanga'
  ];

  const defaultClasses = [
    '1ère Maternelle', '2ème Maternelle', '3ème Maternelle',
    '1ère Primaire', '2ème Primaire', '3ème Primaire', '4ème Primaire', '5ème Primaire', '6ème Primaire',
    '1ère Secondaire', '2ème Secondaire', '3ème Secondaire', '4ème Secondaire', '5ème Secondaire', '6ème Secondaire'
  ];

  const availableClasses = classes && classes.length > 0 ? classes : defaultClasses.map((name, i) => ({ id: name, name }));

  const sections = ['Scientifique', 'Littéraire', 'Commerciale', 'Technique', 'Pédagogique'];

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    
    if (type === 'checkbox') {
      const checked = (e.target as HTMLInputElement).checked;
      setFormData(prev => ({ ...prev, [name]: checked }));
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }
    
    // Clear error for this field
    if (errors[name as keyof StudentFormData]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const validateStep = (step: number): boolean => {
    const newErrors: Partial<Record<keyof StudentFormData, string>> = {};

    if (step === 1) {
      // Personal Information
      if (!formData.matricule) newErrors.matricule = 'Matricule requis';
      if (!formData.firstName) newErrors.firstName = 'Prénom requis';
      if (!formData.lastName) newErrors.lastName = 'Nom requis';
      if (!formData.dateOfBirth) newErrors.dateOfBirth = 'Date de naissance requise';
      if (!formData.placeOfBirth) newErrors.placeOfBirth = 'Lieu de naissance requis';
      if (!formData.nationality) newErrors.nationality = 'Nationalité requise';
    } else if (step === 2) {
      // Contact & Address
      if (!formData.address) newErrors.address = 'Adresse requise';
      if (!formData.city) newErrors.city = 'Ville requise';
      if (!formData.province) newErrors.province = 'Province requise';
      if (formData.phone && !/^(\+243|0)[0-9]{9}$/.test(formData.phone)) {
        newErrors.phone = 'Format: +243XXXXXXXXX ou 0XXXXXXXXX';
      }
      if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
        newErrors.email = 'Email invalide';
      }
    } else if (step === 3) {
      // Guardian Information
      if (!formData.guardianName) newErrors.guardianName = 'Nom du tuteur requis';
      if (!formData.guardianPhone) newErrors.guardianPhone = 'Téléphone du tuteur requis';
      if (!/^(\+243|0)[0-9]{9}$/.test(formData.guardianPhone)) {
        newErrors.guardianPhone = 'Format: +243XXXXXXXXX ou 0XXXXXXXXX';
      }
      if (formData.guardianEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.guardianEmail)) {
        newErrors.guardianEmail = 'Email invalide';
      }
    } else if (step === 4) {
      // Academic Information
      if (!formData.class) newErrors.class = 'Classe requise';
      if (!formData.academicYear) newErrors.academicYear = 'Année académique requise';
      if (!formData.admissionDate) newErrors.admissionDate = 'Date d\'admission requise';
    } else if (step === 5) {
      // Health & Emergency
      if (!formData.emergencyContact) newErrors.emergencyContact = 'Contact d\'urgence requis';
      if (!formData.emergencyPhone) newErrors.emergencyPhone = 'Téléphone d\'urgence requis';
      if (!/^(\+243|0)[0-9]{9}$/.test(formData.emergencyPhone)) {
        newErrors.emergencyPhone = 'Format: +243XXXXXXXXX ou 0XXXXXXXXX';
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => Math.min(prev + 1, 6));
    }
  };

  const handlePrevious = () => {
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };

  const handlePhotoChange = (file: File, preview: string) => {
    setPhotoFile(file);
    setFormData(prev => ({ ...prev, photo: preview }));
  };

  const handlePhotoRemove = () => {
    setPhotoFile(null);
    setFormData(prev => ({ ...prev, photo: '' }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    console.log('Form submission started');
    
    // Validate all steps
    let firstErrorStep = -1;
    let allErrors: any = {};
    
    for (let i = 1; i <= 6; i++) {
      const stepErrors: any = {};
      // Sub-validation logic to collect ALL errors
      if (i === 1) {
        if (!formData.matricule) stepErrors.matricule = 'Matricule requis';
        if (!formData.firstName) stepErrors.firstName = 'Prénom requis';
        if (!formData.lastName) stepErrors.lastName = 'Nom requis';
        if (!formData.dateOfBirth) stepErrors.dateOfBirth = 'Date de naissance requise';
      } else if (i === 4) {
        if (!formData.class) stepErrors.class = 'Classe requise';
      } else if (i === 3) {
        if (!formData.guardianName) stepErrors.guardianName = 'Nom du tuteur requis';
        if (!formData.guardianPhone) stepErrors.guardianPhone = 'Téléphone du tuteur requis';
      }
      
      if (Object.keys(stepErrors).length > 0) {
        if (firstErrorStep === -1) firstErrorStep = i;
        allErrors = { ...allErrors, ...stepErrors };
      }
    }

    if (firstErrorStep !== -1) {
      setErrors(allErrors);
      setCurrentStep(firstErrorStep);
      console.log('Validation failed at step:', firstErrorStep, allErrors);
      alert('Veuillez remplir correctement tous les champs obligatoires (indiqués par *) avant de valider.');
      return;
    }

    console.log('Validation passed, calling onSubmit', formData);
    onSubmit(formData);
  };

  const renderStepIndicator = () => (
    <div className="step-indicator">
      {[1, 2, 3, 4, 5, 6].map(step => (
        <div key={step} className={`step ${currentStep === step ? 'active' : ''} ${currentStep > step ? 'completed' : ''}`}>
          <div className="step-number">{step}</div>
          <div className="step-label">
            {step === 1 && 'Identité'}
            {step === 2 && 'Contact'}
            {step === 3 && 'Tuteur'}
            {step === 4 && 'Académique'}
            {step === 5 && 'Santé'}
            {step === 6 && 'Documents'}
          </div>
        </div>
      ))}
    </div>
  );

  return (
    <div className="student-form-container">
      <div className="student-form-header">
        <h2>{mode === 'create' ? '➕ Nouveau Étudiant' : '✏️ Modifier Étudiant'}</h2>
        {renderStepIndicator()}
      </div>

      <form onSubmit={handleSubmit} className="student-form">
        {/* Step 1: Personal Information */}
        {currentStep === 1 && (
          <div className="form-step">
            <h3>👤 Informations Personnelles</h3>
            
            <PhotoUpload
              currentPhoto={formData.photo}
              onPhotoChange={handlePhotoChange}
              onPhotoRemove={handlePhotoRemove}
              label="Photo de l'élève"
              shape="circle"
            />
            
            <div className="form-row">
              <div className="form-group">
                <label>Matricule *</label>
                <input
                  type="text"
                  name="matricule"
                  value={formData.matricule}
                  onChange={handleChange}
                  placeholder="Ex: STU-2024-001"
                  className={errors.matricule ? 'error' : ''}
                />
                {errors.matricule && <span className="error-message">{errors.matricule}</span>}
              </div>

              <div className="form-group">
                <label>Sexe *</label>
                <select name="gender" value={formData.gender} onChange={handleChange}>
                  <option value="M">Masculin</option>
                  <option value="F">Féminin</option>
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Prénom *</label>
                <input
                  type="text"
                  name="firstName"
                  value={formData.firstName}
                  onChange={handleChange}
                  placeholder="Prénom de l'étudiant"
                  className={errors.firstName ? 'error' : ''}
                />
                {errors.firstName && <span className="error-message">{errors.firstName}</span>}
              </div>

              <div className="form-group">
                <label>Nom *</label>
                <input
                  type="text"
                  name="lastName"
                  value={formData.lastName}
                  onChange={handleChange}
                  placeholder="Nom de l'étudiant"
                  className={errors.lastName ? 'error' : ''}
                />
                {errors.lastName && <span className="error-message">{errors.lastName}</span>}
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Date de Naissance *</label>
                <input
                  type="date"
                  name="dateOfBirth"
                  value={formData.dateOfBirth}
                  onChange={handleChange}
                  className={errors.dateOfBirth ? 'error' : ''}
                />
                {errors.dateOfBirth && <span className="error-message">{errors.dateOfBirth}</span>}
              </div>

              <div className="form-group">
                <label>Lieu de Naissance *</label>
                <input
                  type="text"
                  name="placeOfBirth"
                  value={formData.placeOfBirth}
                  onChange={handleChange}
                  placeholder="Ville, Province"
                  className={errors.placeOfBirth ? 'error' : ''}
                />
                {errors.placeOfBirth && <span className="error-message">{errors.placeOfBirth}</span>}
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Nationalité *</label>
                <input
                  type="text"
                  name="nationality"
                  value={formData.nationality}
                  onChange={handleChange}
                  className={errors.nationality ? 'error' : ''}
                />
                {errors.nationality && <span className="error-message">{errors.nationality}</span>}
              </div>

              <div className="form-group">
                <label>Groupe Sanguin</label>
                <select name="bloodGroup" value={formData.bloodGroup || ''} onChange={handleChange}>
                  <option value="">Sélectionner...</option>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Step 2: Contact & Address */}
        {currentStep === 2 && (
          <div className="form-step">
            <h3>📍 Coordonnées et Adresse</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label>Téléphone</label>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone || ''}
                  onChange={handleChange}
                  placeholder="+243XXXXXXXXX"
                  className={errors.phone ? 'error' : ''}
                />
                {errors.phone && <span className="error-message">{errors.phone}</span>}
              </div>

              <div className="form-group">
                <label>Email</label>
                <input
                  type="email"
                  name="email"
                  value={formData.email || ''}
                  onChange={handleChange}
                  placeholder="email@exemple.com"
                  className={errors.email ? 'error' : ''}
                />
                {errors.email && <span className="error-message">{errors.email}</span>}
              </div>
            </div>

            <div className="form-group">
              <label>Adresse Complète *</label>
              <input
                type="text"
                name="address"
                value={formData.address}
                onChange={handleChange}
                placeholder="N°, Avenue, Commune"
                className={errors.address ? 'error' : ''}
              />
              {errors.address && <span className="error-message">{errors.address}</span>}
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Ville *</label>
                <input
                  type="text"
                  name="city"
                  value={formData.city}
                  onChange={handleChange}
                  placeholder="Ex: Kinshasa"
                  className={errors.city ? 'error' : ''}
                />
                {errors.city && <span className="error-message">{errors.city}</span>}
              </div>

              <div className="form-group">
                <label>Province *</label>
                <select
                  name="province"
                  value={formData.province}
                  onChange={handleChange}
                  className={errors.province ? 'error' : ''}
                >
                  <option value="">Sélectionner une province</option>
                  {provinces.map(prov => (
                    <option key={prov} value={prov}>{prov}</option>
                  ))}
                </select>
                {errors.province && <span className="error-message">{errors.province}</span>}
              </div>
            </div>
          </div>
        )}

        {/* Step 3: Guardian Information */}
        {currentStep === 3 && (
          <div className="form-step">
            <h3>👨‍👩‍👧 Informations du Tuteur</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label>Nom Complet du Tuteur *</label>
                <input
                  type="text"
                  name="guardianName"
                  value={formData.guardianName}
                  onChange={handleChange}
                  placeholder="Nom complet"
                  className={errors.guardianName ? 'error' : ''}
                />
                {errors.guardianName && <span className="error-message">{errors.guardianName}</span>}
              </div>

              <div className="form-group">
                <label>Relation *</label>
                <select name="guardianRelation" value={formData.guardianRelation} onChange={handleChange}>
                  <option value="Père">Père</option>
                  <option value="Mère">Mère</option>
                  <option value="Oncle">Oncle</option>
                  <option value="Tante">Tante</option>
                  <option value="Grand-parent">Grand-parent</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Téléphone du Tuteur *</label>
                <input
                  type="tel"
                  name="guardianPhone"
                  value={formData.guardianPhone}
                  onChange={handleChange}
                  placeholder="+243XXXXXXXXX"
                  className={errors.guardianPhone ? 'error' : ''}
                />
                {errors.guardianPhone && <span className="error-message">{errors.guardianPhone}</span>}
              </div>

              <div className="form-group">
                <label>Email du Tuteur</label>
                <input
                  type="email"
                  name="guardianEmail"
                  value={formData.guardianEmail || ''}
                  onChange={handleChange}
                  placeholder="email@exemple.com"
                  className={errors.guardianEmail ? 'error' : ''}
                />
                {errors.guardianEmail && <span className="error-message">{errors.guardianEmail}</span>}
              </div>
            </div>

            <div className="form-group">
              <label>Adresse du Tuteur</label>
              <input
                type="text"
                name="guardianAddress"
                value={formData.guardianAddress || ''}
                onChange={handleChange}
                placeholder="Adresse complète"
              />
            </div>

            <div className="form-group">
              <label>Profession du Tuteur</label>
              <input
                type="text"
                name="guardianOccupation"
                value={formData.guardianOccupation || ''}
                onChange={handleChange}
                placeholder="Ex: Médecin, Enseignant, Commerçant..."
              />
            </div>
          </div>
        )}

        {/* Step 4: Academic Information */}
        {currentStep === 4 && (
          <div className="form-step">
            <h3>📚 Informations Académiques</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label>Classe *</label>
                <select
                  name="class"
                  value={formData.class}
                  onChange={handleChange}
                  className={errors.class ? 'error' : ''}
                >
                  <option value="">Sélectionner une classe</option>
                  {availableClasses.map(cls => (
                    <option key={cls.id} value={cls.id}>{cls.name}</option>
                  ))}
                </select>
                {errors.class && <span className="error-message">{errors.class}</span>}
              </div>

              <div className="form-group">
                <label>Section (Secondaire uniquement)</label>
                <select name="section" value={formData.section || ''} onChange={handleChange}>
                  <option value="">N/A</option>
                  {sections.map(sec => (
                    <option key={sec} value={sec}>{sec}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Année Académique *</label>
                <input
                  type="text"
                  name="academicYear"
                  value={formData.academicYear}
                  onChange={handleChange}
                  placeholder="2024-2025"
                  className={errors.academicYear ? 'error' : ''}
                />
                {errors.academicYear && <span className="error-message">{errors.academicYear}</span>}
              </div>

              <div className="form-group">
                <label>Date d'Admission *</label>
                <input
                  type="date"
                  name="admissionDate"
                  value={formData.admissionDate}
                  onChange={handleChange}
                  className={errors.admissionDate ? 'error' : ''}
                />
                {errors.admissionDate && <span className="error-message">{errors.admissionDate}</span>}
              </div>
            </div>

            <div className="form-group">
              <label>École Précédente</label>
              <input
                type="text"
                name="previousSchool"
                value={formData.previousSchool || ''}
                onChange={handleChange}
                placeholder="Nom de l'école précédente"
              />
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Statut des Frais Scolaires *</label>
                <select name="tuitionStatus" value={formData.tuitionStatus} onChange={handleChange}>
                  <option value="paid">Payé</option>
                  <option value="partial">Partiel</option>
                  <option value="unpaid">Non Payé</option>
                </select>
              </div>

              <div className="form-group">
                <label>Bourse</label>
                <select name="scholarshipStatus" value={formData.scholarshipStatus || 'none'} onChange={handleChange}>
                  <option value="none">Aucune</option>
                  <option value="partial">Partielle</option>
                  <option value="full">Complète</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Step 5: Health & Emergency */}
        {currentStep === 5 && (
          <div className="form-step">
            <h3>🏥 Santé et Contact d'Urgence</h3>
            
            <div className="form-group">
              <label>Allergies</label>
              <textarea
                name="allergies"
                value={formData.allergies || ''}
                onChange={handleChange}
                placeholder="Mentionner toutes les allergies connues"
                rows={2}
              />
            </div>

            <div className="form-group">
              <label>Conditions Médicales</label>
              <textarea
                name="medicalConditions"
                value={formData.medicalConditions || ''}
                onChange={handleChange}
                placeholder="Asthme, diabète, épilepsie, etc."
                rows={2}
              />
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Contact d'Urgence *</label>
                <input
                  type="text"
                  name="emergencyContact"
                  value={formData.emergencyContact}
                  onChange={handleChange}
                  placeholder="Nom complet"
                  className={errors.emergencyContact ? 'error' : ''}
                />
                {errors.emergencyContact && <span className="error-message">{errors.emergencyContact}</span>}
              </div>

              <div className="form-group">
                <label>Téléphone d'Urgence *</label>
                <input
                  type="tel"
                  name="emergencyPhone"
                  value={formData.emergencyPhone}
                  onChange={handleChange}
                  placeholder="+243XXXXXXXXX"
                  className={errors.emergencyPhone ? 'error' : ''}
                />
                {errors.emergencyPhone && <span className="error-message">{errors.emergencyPhone}</span>}
              </div>
            </div>
          </div>
        )}

        {/* Step 6: Documents & Status */}
        {currentStep === 6 && (
          <div className="form-step">
            <h3>📄 Documents et Statut</h3>
            
            <div className="form-group-checkbox-list">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  name="hasbirthCertificate"
                  checked={formData.hasbirthCertificate}
                  onChange={handleChange}
                />
                <span>Acte de Naissance</span>
              </label>

              <label className="checkbox-label">
                <input
                  type="checkbox"
                  name="hasVaccinationCard"
                  checked={formData.hasVaccinationCard}
                  onChange={handleChange}
                />
                <span>Carnet de Vaccination</span>
              </label>

              <label className="checkbox-label">
                <input
                  type="checkbox"
                  name="hasReportCard"
                  checked={formData.hasReportCard}
                  onChange={handleChange}
                />
                <span>Bulletin de l'Année Précédente</span>
              </label>

              <label className="checkbox-label">
                <input
                  type="checkbox"
                  name="hasPhoto"
                  checked={formData.hasPhoto}
                  onChange={handleChange}
                />
                <span>Photo d'Identité</span>
              </label>
            </div>

            <div className="form-group">
              <label>Statut de l'Étudiant *</label>
              <select name="status" value={formData.status} onChange={handleChange}>
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
                <option value="graduated">Diplômé</option>
                <option value="transferred">Transféré</option>
                <option value="expelled">Expulsé</option>
              </select>
            </div>

            <div className="form-group">
              <label>Notes / Observations</label>
              <textarea
                name="notes"
                value={formData.notes || ''}
                onChange={handleChange}
                placeholder="Informations supplémentaires..."
                rows={4}
              />
            </div>
          </div>
        )}

        <div className="form-actions">
          <button type="button" onClick={onCancel} className="btn-cancel">
            Annuler
          </button>
          
          {currentStep > 1 && (
            <button type="button" onClick={handlePrevious} className="btn-secondary">
              ← Précédent
            </button>
          )}
          
          {currentStep < 6 ? (
            <button type="button" onClick={handleNext} className="btn-primary">
              Suivant →
            </button>
          ) : (
            <button type="submit" className="btn-success">
              {mode === 'create' ? '✓ Créer l\'Étudiant' : '✓ Enregistrer'}
            </button>
          )}
        </div>
      </form>
    </div>
  );
};

export default StudentForm;
