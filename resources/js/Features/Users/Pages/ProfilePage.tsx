import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import PhotoUpload from '../Components/PhotoUpload';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './ProfilePage.css';

const ProfilePage: React.FC = () => {
  const { user } = usePage<any>().props.auth;
  
  const [activeTab, setActiveTab] = useState<'personal' | 'security' | 'preferences'>('personal');
  const [isEditing, setIsEditing] = useState(false);
  
  const [personalInfo, setPersonalInfo] = useState({
    firstName: 'Admin',
    lastName: 'SmartSchool',
    email: 'admin@smartschool.cd',
    phone: '+243 81 234 5678',
    photo: '',
    birthDate: '1990-01-15',
    address: 'Avenue de la Libération, Kinshasa',
    city: 'Kinshasa',
    province: 'Kinshasa',
    bio: 'Administrateur système de SmartSchool RDC'
  });

  const [securityInfo, setSecurityInfo] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
    twoFactorEnabled: false,
    lastPasswordChange: new Date('2025-01-01')
  });

  const [preferences, setPreferences] = useState({
    language: 'fr',
    timezone: 'Africa/Kinshasa',
    emailNotifications: true,
    smsNotifications: false,
    theme: 'light',
    currency: 'CDF',
    dateFormat: 'DD/MM/YYYY'
  });

  const [photoFile, setPhotoFile] = useState<File | null>(null);

  const handlePhotoChange = (file: File, preview: string) => {
    setPhotoFile(file);
    setPersonalInfo(prev => ({ ...prev, photo: preview }));
  };

  const handlePhotoRemove = () => {
    setPhotoFile(null);
    setPersonalInfo(prev => ({ ...prev, photo: '' }));
  };

  const handleSavePersonalInfo = (e: React.FormEvent) => {
    e.preventDefault();
    console.log('Saving personal info:', personalInfo);
    if (photoFile) {
      console.log('Uploading photo:', photoFile);
    }
    setIsEditing(false);
    alert('Profil mis à jour avec succès !');
  };

  const handleChangePassword = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (securityInfo.newPassword !== securityInfo.confirmPassword) {
      alert('Les mots de passe ne correspondent pas !');
      return;
    }

    if (securityInfo.newPassword.length < 8) {
      alert('Le mot de passe doit contenir au moins 8 caractères !');
      return;
    }

    console.log('Changing password');
    alert('Mot de passe modifié avec succès !');
    setSecurityInfo({
      ...securityInfo,
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
      lastPasswordChange: new Date()
    });
  };

  const handleSavePreferences = (e: React.FormEvent) => {
    e.preventDefault();
    console.log('Saving preferences:', preferences);
    alert('Préférences enregistrées avec succès !');
  };

  const provinces = [
    'Kinshasa', 'Kongo-Central', 'Kwango', 'Kwilu', 'Mai-Ndombe',
    'Kasaï', 'Kasaï-Central', 'Kasaï-Oriental', 'Lomami', 'Sankuru',
    'Maniema', 'Sud-Kivu', 'Nord-Kivu', 'Ituri', 'Haut-Uélé', 'Tshopo', 'Bas-Uélé',
    'Nord-Ubangi', 'Mongala', 'Tshuapa', 'Équateur', 'Sud-Ubangi',
    'Lualaba', 'Haut-Katanga', 'Tanganyika', 'Haut-Lomami'
  ];

  return (
    <div className="profile-page">
      <Head title="Profil Utilisateur" />
      <div className="profile-header">
        <div className="profile-banner"></div>
        <div className="profile-info-header">
          <div className="profile-avatar-large">
            {personalInfo.photo ? (
              <img src={personalInfo.photo} alt="Profile" />
            ) : (
              <div className="avatar-placeholder-large">
                {personalInfo.firstName[0]}{personalInfo.lastName[0]}
              </div>
            )}
          </div>
          <div className="profile-header-text">
            <h1>{personalInfo.firstName} {personalInfo.lastName}</h1>
            <p className="profile-role">{user?.role === 'admin' ? 'Administrateur' : user?.role}</p>
            <p className="profile-email">📧 {personalInfo.email}</p>
          </div>
        </div>
      </div>

      <div className="profile-tabs">
        <button
          className={`tab ${activeTab === 'personal' ? 'active' : ''}`}
          onClick={() => setActiveTab('personal')}
        >
          👤 Informations personnelles
        </button>
        <button
          className={`tab ${activeTab === 'security' ? 'active' : ''}`}
          onClick={() => setActiveTab('security')}
        >
          🔒 Sécurité
        </button>
        <button
          className={`tab ${activeTab === 'preferences' ? 'active' : ''}`}
          onClick={() => setActiveTab('preferences')}
        >
          ⚙️ Préférences
        </button>
      </div>

      <div className="profile-content">
        {/* Personal Info Tab */}
        {activeTab === 'personal' && (
          <div className="profile-section">
            <div className="section-header">
              <h2>Informations personnelles</h2>
              {!isEditing && (
                <button className="btn btn-secondary" onClick={() => setIsEditing(true)}>
                  ✏️ Modifier
                </button>
              )}
            </div>

            <form onSubmit={handleSavePersonalInfo}>
              <div className="form-section">
                <PhotoUpload
                  currentPhoto={personalInfo.photo}
                  onPhotoChange={handlePhotoChange}
                  onPhotoRemove={handlePhotoRemove}
                  label="Photo de profil"
                  shape="circle"
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Prénom</label>
                  <input
                    type="text"
                    value={personalInfo.firstName}
                    onChange={(e) => setPersonalInfo({...personalInfo, firstName: e.target.value})}
                    disabled={!isEditing}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Nom</label>
                  <input
                    type="text"
                    value={personalInfo.lastName}
                    onChange={(e) => setPersonalInfo({...personalInfo, lastName: e.target.value})}
                    disabled={!isEditing}
                    required
                  />
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Email</label>
                  <input
                    type="email"
                    value={personalInfo.email}
                    onChange={(e) => setPersonalInfo({...personalInfo, email: e.target.value})}
                    disabled={!isEditing}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Téléphone</label>
                  <input
                    type="tel"
                    value={personalInfo.phone}
                    onChange={(e) => setPersonalInfo({...personalInfo, phone: e.target.value})}
                    disabled={!isEditing}
                    required
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Date de naissance</label>
                <input
                  type="date"
                  value={personalInfo.birthDate}
                  onChange={(e) => setPersonalInfo({...personalInfo, birthDate: e.target.value})}
                  disabled={!isEditing}
                />
              </div>

              <div className="form-group">
                <label>Adresse</label>
                <input
                  type="text"
                  value={personalInfo.address}
                  onChange={(e) => setPersonalInfo({...personalInfo, address: e.target.value})}
                  disabled={!isEditing}
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Ville</label>
                  <input
                    type="text"
                    value={personalInfo.city}
                    onChange={(e) => setPersonalInfo({...personalInfo, city: e.target.value})}
                    disabled={!isEditing}
                  />
                </div>
                <div className="form-group">
                  <label>Province</label>
                  <select
                    value={personalInfo.province}
                    onChange={(e) => setPersonalInfo({...personalInfo, province: e.target.value})}
                    disabled={!isEditing}
                  >
                    {provinces.map(prov => (
                      <option key={prov} value={prov}>{prov}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label>Bio</label>
                <textarea
                  value={personalInfo.bio}
                  onChange={(e) => setPersonalInfo({...personalInfo, bio: e.target.value})}
                  disabled={!isEditing}
                  rows={4}
                  placeholder="Parlez-nous de vous..."
                />
              </div>

              {isEditing && (
                <div className="form-actions">
                  <button type="button" className="btn btn-secondary" onClick={() => setIsEditing(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn btn-primary">
                    💾 Enregistrer
                  </button>
                </div>
              )}
            </form>
          </div>
        )}

        {/* Security Tab */}
        {activeTab === 'security' && (
          <div className="profile-section">
            <div className="section-header">
              <h2>Sécurité du compte</h2>
            </div>

            <div className="security-info-box">
              <div className="info-item">
                <span className="info-icon">🔑</span>
                <div>
                  <strong>Dernière modification du mot de passe</strong>
                  <p>{securityInfo.lastPasswordChange.toLocaleDateString('fr-CD')}</p>
                </div>
              </div>
            </div>

            <form onSubmit={handleChangePassword} className="password-form">
              <h3>Changer le mot de passe</h3>
              
              <div className="form-group">
                <label>Mot de passe actuel</label>
                <input
                  type="password"
                  value={securityInfo.currentPassword}
                  onChange={(e) => setSecurityInfo({...securityInfo, currentPassword: e.target.value})}
                  required
                />
              </div>

              <div className="form-group">
                <label>Nouveau mot de passe</label>
                <input
                  type="password"
                  value={securityInfo.newPassword}
                  onChange={(e) => setSecurityInfo({...securityInfo, newPassword: e.target.value})}
                  minLength={8}
                  required
                />
                <small>Minimum 8 caractères</small>
              </div>

              <div className="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input
                  type="password"
                  value={securityInfo.confirmPassword}
                  onChange={(e) => setSecurityInfo({...securityInfo, confirmPassword: e.target.value})}
                  required
                />
              </div>

              <button type="submit" className="btn btn-primary">
                🔒 Changer le mot de passe
              </button>
            </form>

            <div className="two-factor-section">
              <h3>Authentification à deux facteurs</h3>
              <div className="toggle-option">
                <div>
                  <strong>Activer l'authentification à deux facteurs</strong>
                  <p>Ajoutez une couche de sécurité supplémentaire à votre compte</p>
                </div>
                <label className="toggle-switch">
                  <input
                    type="checkbox"
                    checked={securityInfo.twoFactorEnabled}
                    onChange={(e) => setSecurityInfo({...securityInfo, twoFactorEnabled: e.target.checked})}
                  />
                  <span className="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
        )}

        {/* Preferences Tab */}
        {activeTab === 'preferences' && (
          <div className="profile-section">
            <div className="section-header">
              <h2>Préférences</h2>
            </div>

            <form onSubmit={handleSavePreferences}>
              <h3>Langue et région</h3>
              <div className="form-row">
                <div className="form-group">
                  <label>Langue</label>
                  <select
                    value={preferences.language}
                    onChange={(e) => setPreferences({...preferences, language: e.target.value})}
                  >
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                    <option value="ln">Lingala</option>
                    <option value="sw">Swahili</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Fuseau horaire</label>
                  <select
                    value={preferences.timezone}
                    onChange={(e) => setPreferences({...preferences, timezone: e.target.value})}
                  >
                    <option value="Africa/Kinshasa">Africa/Kinshasa (WAT)</option>
                    <option value="Africa/Lubumbashi">Africa/Lubumbashi (CAT)</option>
                  </select>
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Devise par défaut</label>
                  <select
                    value={preferences.currency}
                    onChange={(e) => setPreferences({...preferences, currency: e.target.value})}
                  >
                    <option value="CDF">Franc Congolais (CDF)</option>
                    <option value="USD">Dollar US (USD)</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Format de date</label>
                  <select
                    value={preferences.dateFormat}
                    onChange={(e) => setPreferences({...preferences, dateFormat: e.target.value})}
                  >
                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                  </select>
                </div>
              </div>

              <h3>Notifications</h3>
              <div className="toggle-option">
                <div>
                  <strong>Notifications par email</strong>
                  <p>Recevoir des notifications importantes par email</p>
                </div>
                <label className="toggle-switch">
                  <input
                    type="checkbox"
                    checked={preferences.emailNotifications}
                    onChange={(e) => setPreferences({...preferences, emailNotifications: e.target.checked})}
                  />
                  <span className="toggle-slider"></span>
                </label>
              </div>

              <div className="toggle-option">
                <div>
                  <strong>Notifications par SMS</strong>
                  <p>Recevoir des alertes urgentes par SMS</p>
                </div>
                <label className="toggle-switch">
                  <input
                    type="checkbox"
                    checked={preferences.smsNotifications}
                    onChange={(e) => setPreferences({...preferences, smsNotifications: e.target.checked})}
                  />
                  <span className="toggle-slider"></span>
                </label>
              </div>

              <h3>Apparence</h3>
              <div className="form-group">
                <label>Thème</label>
                <select
                  value={preferences.theme}
                  onChange={(e) => setPreferences({...preferences, theme: e.target.value})}
                >
                  <option value="light">Clair</option>
                  <option value="dark">Sombre</option>
                  <option value="auto">Automatique</option>
                </select>
              </div>

              <div className="form-actions">
                <button type="submit" className="btn btn-primary">
                  💾 Enregistrer les préférences
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};

(ProfilePage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default ProfilePage;
