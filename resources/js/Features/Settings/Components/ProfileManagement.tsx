import React, { useState } from 'react';
import './ProfileManagement.css';

export interface Profile {
  id: string;
  name: string;
  description: string;
  permissions: string[];
  createdAt: Date;
  updatedAt: Date;
}

const ProfileManagement: React.FC = () => {
  const [profiles, setProfiles] = useState<Profile[]>([
    {
      id: 'admin',
      name: 'Administrateur',
      description: 'Accès complet au système',
      permissions: ['*'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: 'director',
      name: 'Directeur',
      description: 'Supervision et rapports',
      permissions: ['students:read', 'teachers:read', 'reports:*', 'finance:read'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: 'teacher',
      name: 'Enseignant',
      description: 'Gestion des classes et élèves',
      permissions: ['students:read', 'students:write', 'classes:*', 'grades:*'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: 'accountant',
      name: 'Comptable',
      description: 'Gestion financière',
      permissions: ['finance:*', 'payments:*', 'students:read'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: 'secretary',
      name: 'Secrétaire',
      description: 'Gestion administrative',
      permissions: ['students:*', 'admissions:*', 'communication:write'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: 'parent',
      name: 'Parent',
      description: 'Suivi des enfants',
      permissions: ['students:read_own', 'payments:read_own', 'messages:read'],
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    }
  ]);

  const [showProfileForm, setShowProfileForm] = useState(false);
  const [editingProfile, setEditingProfile] = useState<Profile | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    permissions: [] as string[]
  });

  const permissionCategories = [
    {
      category: 'Élèves',
      permissions: [
        { id: 'students:read', label: 'Voir les élèves' },
        { id: 'students:write', label: 'Créer/Modifier les élèves' },
        { id: 'students:delete', label: 'Supprimer les élèves' },
        { id: 'students:*', label: 'Gestion complète des élèves' }
      ]
    },
    {
      category: 'Classes',
      permissions: [
        { id: 'classes:read', label: 'Voir les classes' },
        { id: 'classes:write', label: 'Gérer les classes' },
        { id: 'classes:*', label: 'Gestion complète des classes' }
      ]
    },
    {
      category: 'Notes',
      permissions: [
        { id: 'grades:read', label: 'Voir les notes' },
        { id: 'grades:write', label: 'Saisir/Modifier les notes' },
        { id: 'grades:*', label: 'Gestion complète des notes' }
      ]
    },
    {
      category: 'Finance',
      permissions: [
        { id: 'finance:read', label: 'Voir les finances' },
        { id: 'finance:write', label: 'Gérer les finances' },
        { id: 'payments:read', label: 'Voir les paiements' },
        { id: 'payments:write', label: 'Enregistrer les paiements' },
        { id: 'finance:*', label: 'Gestion complète des finances' }
      ]
    },
    {
      category: 'Admissions',
      permissions: [
        { id: 'admissions:read', label: 'Voir les demandes' },
        { id: 'admissions:write', label: 'Gérer les demandes' },
        { id: 'admissions:*', label: 'Gestion complète des admissions' }
      ]
    },
    {
      category: 'Communication',
      permissions: [
        { id: 'communication:read', label: 'Voir les messages' },
        { id: 'communication:write', label: 'Envoyer des messages' },
        { id: 'announcements:write', label: 'Créer des annonces' }
      ]
    },
    {
      category: 'Événements',
      permissions: [
        { id: 'events:read', label: 'Voir les événements' },
        { id: 'events:write', label: 'Créer/Modifier les événements' },
        { id: 'events:*', label: 'Gestion complète des événements' }
      ]
    },
    {
      category: 'Inventaire',
      permissions: [
        { id: 'inventory:read', label: 'Voir l\'inventaire' },
        { id: 'inventory:write', label: 'Gérer l\'inventaire' }
      ]
    },
    {
      category: 'Utilisateurs',
      permissions: [
        { id: 'users:read', label: 'Voir les utilisateurs' },
        { id: 'users:write', label: 'Gérer les utilisateurs' }
      ]
    },
    {
      category: 'Paramètres',
      permissions: [
        { id: 'settings:read', label: 'Voir les paramètres' },
        { id: 'settings:write', label: 'Modifier les paramètres' }
      ]
    },
    {
      category: 'Rapports',
      permissions: [
        { id: 'reports:read', label: 'Voir les rapports' },
        { id: 'reports:*', label: 'Générer tous les rapports' }
      ]
    },
    {
      category: 'Super Admin',
      permissions: [
        { id: '*', label: '⚠️ Tous les droits (Accès complet)' }
      ]
    }
  ];

  const handleAddProfile = () => {
    setEditingProfile(null);
    setFormData({ name: '', description: '', permissions: [] });
    setShowProfileForm(true);
  };

  const handleEditProfile = (profile: Profile) => {
    setEditingProfile(profile);
    setFormData({
      name: profile.name,
      description: profile.description,
      permissions: profile.permissions
    });
    setShowProfileForm(true);
  };

  const handleDeleteProfile = (profileId: string) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce profil ?')) {
      setProfiles(prev => prev.filter(p => p.id !== profileId));
    }
  };

  const handleSubmitProfile = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (editingProfile) {
      setProfiles(prev => prev.map(p => 
        p.id === editingProfile.id 
          ? { ...p, ...formData, updatedAt: new Date() }
          : p
      ));
    } else {
      const newProfile: Profile = {
        id: formData.name.toLowerCase().replace(/\s+/g, '_'),
        ...formData,
        createdAt: new Date(),
        updatedAt: new Date()
      };
      setProfiles(prev => [...prev, newProfile]);
    }
    
    setShowProfileForm(false);
  };

  const handlePermissionToggle = (permissionId: string) => {
    setFormData(prev => ({
      ...prev,
      permissions: prev.permissions.includes(permissionId)
        ? prev.permissions.filter(p => p !== permissionId)
        : [...prev.permissions, permissionId]
    }));
  };

  const getPermissionCount = (profile: Profile) => {
    if (profile.permissions.includes('*')) return 'Tous les droits';
    return `${profile.permissions.length} permissions`;
  };

  return (
    <div className="profile-management">
      <div className="profile-header">
        <div>
          <h2>👥 Profils & Permissions</h2>
          <p>Créez et gérez les profils d'accès avec leurs permissions associées</p>
        </div>
        <button className="btn btn-primary" onClick={handleAddProfile}>
          <span>➕</span>
          Nouveau Profil
        </button>
      </div>

      <div className="profiles-grid">
        {profiles.map(profile => (
          <div key={profile.id} className="profile-card">
            <div className="profile-card-header">
              <div className="profile-icon">
                {profile.permissions.includes('*') ? '👑' : '👤'}
              </div>
              <div className="profile-info">
                <h3>{profile.name}</h3>
                <p>{profile.description}</p>
              </div>
            </div>

            <div className="profile-stats">
              <span className="permission-count">
                {getPermissionCount(profile)}
              </span>
            </div>

            <div className="profile-permissions-preview">
              {profile.permissions.slice(0, 3).map(perm => (
                <span key={perm} className="permission-badge">{perm}</span>
              ))}
              {profile.permissions.length > 3 && (
                <span className="permission-badge more">+{profile.permissions.length - 3}</span>
              )}
            </div>

            <div className="profile-card-actions">
              <button 
                className="btn-icon" 
                onClick={() => handleEditProfile(profile)}
                title="Modifier"
              >
                ✏️
              </button>
              <button 
                className="btn-icon danger" 
                onClick={() => handleDeleteProfile(profile.id)}
                title="Supprimer"
                disabled={['admin', 'parent'].includes(profile.id)}
              >
                🗑️
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Profile Form Modal */}
      {showProfileForm && (
        <div className="modal-overlay">
          <div className="modal-content large">
            <div className="modal-header">
              <h2>{editingProfile ? 'Modifier le profil' : 'Nouveau profil'}</h2>
              <button className="close-btn" onClick={() => setShowProfileForm(false)}>×</button>
            </div>

            <form onSubmit={handleSubmitProfile} className="profile-form">
              <div className="form-group">
                <label>Nom du profil <span className="required">*</span></label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({...formData, name: e.target.value})}
                  placeholder="Ex: Directeur pédagogique"
                  required
                />
              </div>

              <div className="form-group">
                <label>Description <span className="required">*</span></label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({...formData, description: e.target.value})}
                  placeholder="Décrivez les responsabilités de ce profil..."
                  rows={3}
                  required
                />
              </div>

              <div className="form-section">
                <h3>Permissions</h3>
                <p className="section-subtitle">Sélectionnez les permissions à accorder à ce profil</p>

                <div className="permissions-container">
                  {permissionCategories.map(cat => (
                    <div key={cat.category} className="permission-category">
                      <h4>{cat.category}</h4>
                      <div className="permission-list">
                        {cat.permissions.map(perm => (
                          <label key={perm.id} className="permission-checkbox">
                            <input
                              type="checkbox"
                              checked={formData.permissions.includes(perm.id)}
                              onChange={() => handlePermissionToggle(perm.id)}
                            />
                            <span>{perm.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="form-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setShowProfileForm(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingProfile ? 'Enregistrer' : 'Créer le profil'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProfileManagement;
