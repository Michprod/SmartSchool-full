import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import PhotoUpload from '../Components/PhotoUpload';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './ProfilePage.css';
import axios from 'axios';

interface UserData {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  role: string;
  department: string | null;
  birth_date: string | null;
  address: string | null;
  city: string | null;
  province: string | null;
  bio: string | null;
}

const ProfilePage: React.FC = () => {
  const { auth } = usePage<any>().props;
  const currentUser = auth?.user;

  const [activeTab, setActiveTab] = useState<'personal' | 'security' | 'preferences'>('personal');
  const [isEditing, setIsEditing] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  
  const [formData, setFormData] = useState<UserData>({
    id: currentUser?.id || 0,
    first_name: currentUser?.first_name || '',
    last_name: currentUser?.last_name || '',
    email: currentUser?.email || '',
    phone: currentUser?.phone || '',
    avatar: currentUser?.avatar || '',
    role: currentUser?.role || '',
    department: currentUser?.department || '',
    birth_date: '',
    address: '',
    city: '',
    province: '',
    bio: ''
  });

  const [passwordData, setPasswordData] = useState({
    current_password: '',
    password: '',
    password_confirmation: ''
  });

  const [photoFile, setPhotoFile] = useState<File | null>(null);

  const provinces = [
    'Kinshasa', 'Kongo-Central', 'Kwango', 'Kwilu', 'Mai-Ndombe',
    'Kasaï', 'Kasaï-Central', 'Kasaï-Oriental', 'Lomami', 'Sankuru',
    'Maniema', 'Sud-Kivu', 'Nord-Kivu', 'Ituri', 'Haut-Uélé', 'Tshopo', 'Bas-Uélé',
    'Nord-Ubangi', 'Mongala', 'Tshuapa', 'Équateur', 'Sud-Ubangi',
    'Lualaba', 'Haut-Katanga', 'Tanganyika', 'Haut-Lomami'
  ];

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/user');
      const user = response.data;
      
      setFormData({
        id: user.id,
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        phone: user.phone || '',
        avatar: user.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/storage/${user.avatar}`) : '',
        role: user.role || '',
        department: user.department || '',
        birth_date: user.birth_date || '',
        address: user.address || '',
        city: user.city || '',
        province: user.province || '',
        bio: user.bio || ''
      });
    } catch (error: any) {
      console.error('Error loading profile:', error);
      showMessage('error', 'Erreur lors du chargement du profil');
    } finally {
      setLoading(false);
    }
  };

  const showMessage = (type: 'success' | 'error', text: string) => {
    setMessage({ type, text });
    setTimeout(() => setMessage(null), 4000);
  };

  const handlePhotoChange = (file: File, preview: string) => {
    setPhotoFile(file);
    setFormData(prev => ({ ...prev, avatar: preview }));
  };

  const handlePhotoRemove = () => {
    setPhotoFile(null);
    setFormData(prev => ({ ...prev, avatar: '' }));
  };

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      setSaving(true);
      
      const payload: any = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
        phone: formData.phone || null,
      };

      if (formData.birth_date) payload.birth_date = formData.birth_date;
      if (formData.address) payload.address = formData.address;
      if (formData.city) payload.city = formData.city;
      if (formData.province) payload.province = formData.province;
      if (formData.bio) payload.bio = formData.bio;

      if (photoFile) {
        const base64 = await new Promise<string>((resolve) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result as string);
          reader.readAsDataURL(photoFile);
        });
        payload.avatar = base64;
      }

      await axios.put(`/api/users/${formData.id}`, payload);
      
      showMessage('success', 'Profil mis à jour avec succès !');
      setIsEditing(false);
      setPhotoFile(null);
      await loadProfile();
    } catch (error: any) {
      console.error('Error saving profile:', error);
      const errorMsg = error.response?.data?.message || 'Erreur lors de la sauvegarde';
      showMessage('error', errorMsg);
    } finally {
      setSaving(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (passwordData.password !== passwordData.password_confirmation) {
      showMessage('error', 'Les mots de passe ne correspondent pas');
      return;
    }

    if (passwordData.password.length < 8) {
      showMessage('error', 'Le mot de passe doit contenir au moins 8 caractères');
      return;
    }

    try {
      setSaving(true);
      
      await axios.put(`/api/users/${formData.id}`, {
        password: passwordData.password,
        password_confirmation: passwordData.password_confirmation
      });

      showMessage('success', 'Mot de passe modifié avec succès !');
      setPasswordData({
        current_password: '',
        password: '',
        password_confirmation: ''
      });
    } catch (error: any) {
      console.error('Error changing password:', error);
      showMessage('error', error.response?.data?.message || 'Erreur lors du changement du mot de passe');
    } finally {
      setSaving(false);
    }
  };

  const getRoleLabel = (role: string): string => {
    const labels: Record<string, string> = {
      admin: 'Administrateur',
      director: 'Directeur',
      teacher: 'Enseignant',
      accountant: 'Comptable',
      secretary: 'Secrétaire',
      parent: 'Parent'
    };
    return labels[role] || role;
  };

  if (loading) {
    return (
      <div className="profile-loading">
        <div className="spinner"></div>
        <p>Chargement du profil...</p>
      </div>
    );
  }

  return (
    <div className="profile-page-new">
      <Head title="Mon Profil" />

      {/* Message Display */}
      {message && (
        <div className={`profile-message ${message.type}`}>
          <span className="message-icon">{message.type === 'success' ? '✓' : '✕'}</span>
          {message.text}
        </div>
      )}

      {/* Profile Header */}
      <div className="profile-header-new">
        <div className="profile-banner"></div>
        <div className="profile-info-section">
          <div className="profile-avatar-wrapper">
            {formData.avatar ? (
              <img src={formData.avatar} alt="Profile" />
            ) : (
              <div className="avatar-placeholder-new">
                {formData.first_name?.charAt(0) || ''}{formData.last_name?.charAt(0) || ''}
              </div>
            )}
          </div>
          <div className="profile-details">
            <h1>{formData.first_name} {formData.last_name}</h1>
            <p className="role-badge">{getRoleLabel(formData.role)}</p>
            <p className="email-text">📧 {formData.email}</p>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="profile-tabs-new">
        <button
          className={`tab-btn ${activeTab === 'personal' ? 'active' : ''}`}
          onClick={() => setActiveTab('personal')}
        >
          👤 Informations personnelles
        </button>
        <button
          className={`tab-btn ${activeTab === 'security' ? 'active' : ''}`}
          onClick={() => setActiveTab('security')}
        >
          🔒 Sécurité
        </button>
        <button
          className={`tab-btn ${activeTab === 'preferences' ? 'active' : ''}`}
          onClick={() => setActiveTab('preferences')}
        >
          ⚙️ Préférences
        </button>
      </div>

      {/* Content */}
      <div className="profile-content-new">
        {/* Personal Information Tab */}
        {activeTab === 'personal' && (
          <div className="profile-section-new">
            <div className="section-title">
              <h2>Informations personnelles</h2>
              {!isEditing && (
                <button className="btn-edit" onClick={() => setIsEditing(true)}>
                  ✏️ Modifier
                </button>
              )}
            </div>

            <form onSubmit={handleSaveProfile} className="profile-form">
              {/* Photo Upload */}
              <div className="photo-section">
                <PhotoUpload
                  currentPhoto={formData.avatar}
                  onPhotoChange={handlePhotoChange}
                  onPhotoRemove={handlePhotoRemove}
                  label="Photo de profil"
                  shape="circle"
                  disabled={!isEditing}
                />
              </div>

              {/* Name Fields */}
              <div className="form-grid">
                <div className="form-field">
                  <label>Prénom *</label>
                  <input
                    type="text"
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                    disabled={!isEditing}
                    required
                    placeholder="Votre prénom"
                  />
                </div>

                <div className="form-field">
                  <label>Nom *</label>
                  <input
                    type="text"
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                    disabled={!isEditing}
                    required
                    placeholder="Votre nom"
                  />
                </div>
              </div>

              {/* Contact Fields */}
              <div className="form-grid">
                <div className="form-field">
                  <label>Email *</label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    disabled={!isEditing}
                    required
                    placeholder="votre@email.com"
                  />
                </div>

                <div className="form-field">
                  <label>Téléphone</label>
                  <input
                    type="tel"
                    value={formData.phone || ''}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    disabled={!isEditing}
                    placeholder="+243 XX XXX XXXX"
                  />
                </div>
              </div>

              {/* Birth Date */}
              <div className="form-field">
                <label>Date de naissance</label>
                <input
                  type="date"
                  value={formData.birth_date || ''}
                  onChange={(e) => setFormData({ ...formData, birth_date: e.target.value })}
                  disabled={!isEditing}
                />
              </div>

              {/* Address */}
              <div className="form-field">
                <label>Adresse</label>
                <input
                  type="text"
                  value={formData.address || ''}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  disabled={!isEditing}
                  placeholder="Votre adresse complète"
                />
              </div>

              {/* City & Province */}
              <div className="form-grid">
                <div className="form-field">
                  <label>Ville</label>
                  <input
                    type="text"
                    value={formData.city || ''}
                    onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                    disabled={!isEditing}
                    placeholder="Votre ville"
                  />
                </div>

                <div className="form-field">
                  <label>Province</label>
                  <select
                    value={formData.province || ''}
                    onChange={(e) => setFormData({ ...formData, province: e.target.value })}
                    disabled={!isEditing}
                  >
                    <option value="">Sélectionner une province</option>
                    {provinces.map(prov => (
                      <option key={prov} value={prov}>{prov}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Bio */}
              <div className="form-field">
                <label>Biographie</label>
                <textarea
                  value={formData.bio || ''}
                  onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
                  disabled={!isEditing}
                  rows={4}
                  placeholder="Parlez-nous de vous..."
                />
              </div>

              {/* Action Buttons */}
              {isEditing && (
                <div className="form-actions-new">
                  <button
                    type="button"
                    className="btn-cancel"
                    onClick={() => {
                      setIsEditing(false);
                      loadProfile();
                    }}
                    disabled={saving}
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    className="btn-save"
                    disabled={saving}
                  >
                    {saving ? (
                      <>
                        <span className="btn-spinner"></span>
                        Enregistrement...
                      </>
                    ) : (
                      <>💾 Enregistrer</>
                    )}
                  </button>
                </div>
              )}
            </form>
          </div>
        )}

        {/* Security Tab */}
        {activeTab === 'security' && (
          <div className="profile-section-new">
            <div className="section-title">
              <h2>Sécurité du compte</h2>
            </div>

            <form onSubmit={handleChangePassword} className="password-form-new">
              <h3>Changer le mot de passe</h3>
              
              <div className="form-field">
                <label>Mot de passe actuel</label>
                <input
                  type="password"
                  value={passwordData.current_password}
                  onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                  required
                />
              </div>

              <div className="form-field">
                <label>Nouveau mot de passe</label>
                <input
                  type="password"
                  value={passwordData.password}
                  onChange={(e) => setPasswordData({ ...passwordData, password: e.target.value })}
                  minLength={8}
                  required
                />
                <small>Minimum 8 caractères</small>
              </div>

              <div className="form-field">
                <label>Confirmer le mot de passe</label>
                <input
                  type="password"
                  value={passwordData.password_confirmation}
                  onChange={(e) => setPasswordData({ ...passwordData, password_confirmation: e.target.value })}
                  required
                />
              </div>

              <button type="submit" className="btn-save" disabled={saving}>
                {saving ? 'Modification...' : '🔒 Changer le mot de passe'}
              </button>
            </form>
          </div>
        )}

        {/* Preferences Tab */}
        {activeTab === 'preferences' && (
          <div className="profile-section-new">
            <div className="section-title">
              <h2>Préférences</h2>
            </div>
            <p style={{ color: '#7f8c8d', padding: '20px' }}>
              Les préférences seront disponibles dans une prochaine mise à jour.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

(ProfilePage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default ProfilePage;
