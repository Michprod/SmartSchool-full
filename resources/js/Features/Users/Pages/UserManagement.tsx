import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import Pagination from '../../../Core/Components/Pagination';
import './UserManagement.css';
import axios from 'axios';
import PhotoUpload from '../Components/PhotoUpload';

export interface User {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  role: 'admin' | 'director' | 'teacher' | 'accountant' | 'secretary' | 'parent';
  status: 'active' | 'inactive' | 'suspended';
  photo?: string;
  department?: string;
  permissions: string[];
  createdAt: Date;
  lastLogin?: Date;
}

const UserManagement: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRole, setSelectedRole] = useState<string>('all');
  const [showUserForm, setShowUserForm] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  const [availableRoles, setAvailableRoles] = useState<any[]>([]);

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm, selectedRole]);

  // User Form State
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    role: 'teacher' as User['role'],
    profileId: '',
    department: '',
    status: 'active' as User['status'],
    photo: '',
    password: ''
  });

  const [formErrors, setFormErrors] = useState<Record<string, string>>({});

  const [photoFile, setPhotoFile] = useState<File | null>(null);
  
  // Log photoFile for debugging
  console.log('Photo file state:', photoFile);

  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        // Fetch users and roles in parallel
        const [usersRes, rolesRes] = await Promise.all([
          axios.get('/api/users'),
          axios.get('/api/roles')
        ]);

        // Map users
        const userData = Array.isArray(usersRes.data) ? usersRes.data : (usersRes.data.data || []);
        const mappedUsers = userData.map((u: any) => ({
          id: u.id.toString(),
          firstName: u.first_name,
          lastName: u.last_name,
          email: u.email,
          phone: u.phone,
          role: u.role,
          status: u.is_active ? 'active' : 'inactive',
          photo: u.avatar ? (u.avatar.startsWith('http') ? u.avatar : `/storage/${u.avatar}`) : undefined,
          department: u.department,
          permissions: Array.isArray(u.permissions) ? u.permissions : [],
          createdAt: new Date(u.created_at),
          lastLogin: u.last_login ? new Date(u.last_login) : undefined
        }));

        setUsers(mappedUsers);
        setAvailableRoles(rolesRes.data);
      } catch (error) {
        console.error('Error loading data:', error);
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, []);

  const roleLabels: Record<User['role'], string> = {
    admin: 'Administrateur',
    director: 'Directeur',
    teacher: 'Enseignant',
    accountant: 'Comptable',
    secretary: 'Secrétaire',
    parent: 'Parent'
  };

  const statusLabels: Record<User['status'], string> = {
    active: 'Actif',
    inactive: 'Inactif',
    suspended: 'Suspendu'
  };

  const statusColors: Record<User['status'], string> = {
    active: '#27ae60',
    inactive: '#95a5a6',
    suspended: '#e74c3c'
  };

  const filteredUsers = users.filter(user => {
    const matchesSearch = 
      user.firstName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.lastName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesRole = selectedRole === 'all' || user.role === selectedRole;
    
    return matchesSearch && matchesRole;
  });

  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentUsers = filteredUsers.slice(indexOfFirstItem, indexOfLastItem);

  const handleAddUser = () => {
    setEditingUser(null);
    setFormData({
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      role: 'teacher',
      profileId: '',
      department: '',
      status: 'active',
      photo: '',
      password: ''
    });
    setPhotoFile(null);
    setFormErrors({});
    setShowUserForm(true);
  };

  const handleEditUser = (user: User) => {
    setEditingUser(user);
    setFormData({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      phone: user.phone,
      role: user.role,
      profileId: user.role, // Map role to profileId for now
      department: user.department || '',
      status: user.status,
      photo: user.photo || ''
    });
    setShowUserForm(true);
  };

  const handleSubmitUser = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormErrors({});
    
    try {
      const payload: any = {
        first_name: formData.firstName,
        last_name: formData.lastName,
        email: formData.email,
        phone: formData.phone,
        role: formData.role,
        department: formData.department,
        is_active: formData.status === 'active',
        avatar: formData.photo
      };

      // Only include password for new users or if provided
      if (!editingUser || formData.password) {
        payload.password = formData.password || 'password123';
      }

      if (editingUser) {
        // Update user
        const response = await axios.put(`/api/users/${editingUser.id}`, payload);
        const updatedUser = response.data;
        
        setUsers(prev => prev.map(u => 
          u.id === editingUser.id 
            ? { 
                ...u, 
                firstName: formData.firstName,
                lastName: formData.lastName,
                email: formData.email,
                phone: formData.phone,
                role: formData.role,
                department: formData.department,
                status: formData.status,
                photo: updatedUser.avatar ? (updatedUser.avatar.startsWith('http') ? updatedUser.avatar : `/storage/${updatedUser.avatar}`) : formData.photo
              }
            : u
        ));
      } else {
        // Add new user
        const response = await axios.post('/api/users', payload);
        const data = response.data;
        const newUser: User = {
          id: data.id ? data.id.toString() : `user-${Date.now()}`,
          firstName: formData.firstName,
          lastName: formData.lastName,
          email: formData.email,
          phone: formData.phone,
          role: formData.role,
          department: formData.department,
          status: formData.status,
          photo: data.avatar ? (data.avatar.startsWith('http') ? data.avatar : `/storage/${data.avatar}`) : formData.photo,
          permissions: data.permissions || [], 
          createdAt: new Date()
        };
        setUsers(prev => [...prev, newUser]);
      }
      
      setShowUserForm(false);
      setEditingUser(null);
    } catch (error: any) {
      console.error('Error submitting user:', error);
      if (error.response?.data?.errors) {
        // Handle validation errors
        const errors: Record<string, string> = {};
        Object.entries(error.response.data.errors).forEach(([key, messages]) => {
          errors[key] = Array.isArray(messages) ? messages[0] : String(messages);
        });
        setFormErrors(errors);
      } else {
        alert(error.response?.data?.message || "Erreur lors de l'enregistrement.");
      }
    }
  };

  const handleDeleteUser = async (userId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
      try {
        await axios.delete(`/api/users/${userId}`);
        setUsers(prev => prev.filter(u => u.id !== userId));
      } catch (error: any) {
        console.error('Error deleting user:', error);
        alert(error.response?.data?.message || "Erreur lors de la suppression.");
      }
    }
  };

  const handlePhotoChange = (file: File, preview: string) => {
    setPhotoFile(file);
    setFormData(prev => ({ ...prev, photo: preview }));
  };

  const handlePhotoRemove = () => {
    setPhotoFile(null);
    setFormData(prev => ({ ...prev, photo: '' }));
  };

  // Available profiles - synced from ProfileManagement in Settings
  const availableProfiles = [
    { id: 'admin', name: 'Administrateur', description: 'Accès complet au système' },
    { id: 'director', name: 'Directeur', description: 'Supervision et rapports' },
    { id: 'teacher', name: 'Enseignant', description: 'Gestion des classes et élèves' },
    { id: 'accountant', name: 'Comptable', description: 'Gestion financière' },
    { id: 'secretary', name: 'Secrétaire', description: 'Gestion administrative' },
    { id: 'parent', name: 'Parent', description: 'Suivi des enfants' },
  ];

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Chargement des utilisateurs...</p>
      </div>
    );
  }

  return (
    <div className="user-management">
      <Head title="Gestion des Utilisateurs" />
      <div className="page-header">
        <div>
          <h1>Gestion des Utilisateurs</h1>
          <p className="page-subtitle">
            {users.length} utilisateur{users.length > 1 ? 's' : ''} • Gestion des rôles et permissions
          </p>
        </div>
        <button className="btn btn-primary" onClick={handleAddUser}>
          <span>➕</span>
          Nouvel Utilisateur
        </button>
      </div>

      {/* Filters */}
      <div className="filters-bar">
        <div className="search-box">
          <span className="search-icon">🔍</span>
          <input
            type="text"
            placeholder="Rechercher un utilisateur..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <select
          value={selectedRole}
          onChange={(e) => setSelectedRole(e.target.value)}
          className="filter-select"
        >
          <option value="all">Tous les rôles</option>
          {availableRoles.map((role) => (
            <option key={role.slug} value={role.slug}>{role.name}</option>
          ))}
        </select>
      </div>

      {/* Users Table */}
      <div className="users-table-container">
        <table className="users-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Email / Téléphone</th>
              <th>Rôle</th>
              <th>Département</th>
              <th>Statut</th>
              <th>Dernière connexion</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {currentUsers.map(user => (
              <tr key={user.id}>
                <td>
                  <div className="user-info">
                    <div className="user-avatar">
                      {user.photo ? (
                        <img src={user.photo} alt={`${user.firstName} ${user.lastName}`} />
                      ) : (
                        <div className="avatar-placeholder">
                          {user.firstName[0]}{user.lastName[0]}
                        </div>
                      )}
                    </div>
                    <div>
                      <div className="user-name">{user.firstName} {user.lastName}</div>
                      <div className="user-id">ID: {user.id}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div className="contact-info">
                    <div>📧 {user.email}</div>
                    <div>📱 {user.phone}</div>
                  </div>
                </td>
                <td>
                  <span className="role-badge">
                    {availableRoles.find(r => r.slug === user.role)?.name || user.role}
                  </span>
                </td>
                <td>{user.department || '—'}</td>
                <td>
                  <span 
                    className="status-badge"
                    style={{ backgroundColor: statusColors[user.status] }}
                  >
                    {statusLabels[user.status]}
                  </span>
                </td>
                <td>
                  {user.lastLogin 
                    ? new Date(user.lastLogin).toLocaleDateString('fr-CD')
                    : 'Jamais'
                  }
                </td>
                <td>
                  <div className="action-buttons">
                    <button
                      className="action-btn edit"
                      onClick={() => handleEditUser(user)}
                      title="Modifier"
                    >
                      ✏️
                    </button>
                    <button
                      className="action-btn delete"
                      onClick={() => handleDeleteUser(user.id)}
                      title="Supprimer"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination 
          currentPage={currentPage}
          totalItems={filteredUsers.length}
          itemsPerPage={itemsPerPage}
          onPageChange={setCurrentPage}
        />
      </div>

      {filteredUsers.length === 0 && (
        <div className="empty-state">
          <span className="empty-icon">👥</span>
          <h3>Aucun utilisateur trouvé</h3>
          <p>Aucun utilisateur ne correspond à vos critères de recherche.</p>
        </div>
      )}

      {/* User Form Modal */}
      {showUserForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h2>{editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'}</h2>
              <button className="close-btn" onClick={() => setShowUserForm(false)}>×</button>
            </div>

            <form onSubmit={handleSubmitUser} className="user-form">
              <div className="form-section">
                <PhotoUpload
                  currentPhoto={formData.photo}
                  onPhotoChange={handlePhotoChange}
                  onPhotoRemove={handlePhotoRemove}
                  label="Photo de profil"
                  shape="circle"
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Prénom <span className="required">*</span></label>
                  <input
                    type="text"
                    value={formData.firstName}
                    onChange={(e) => setFormData({...formData, firstName: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Nom <span className="required">*</span></label>
                  <input
                    type="text"
                    value={formData.lastName}
                    onChange={(e) => setFormData({...formData, lastName: e.target.value})}
                    required
                  />
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Email <span className="required">*</span></label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({...formData, email: e.target.value})}
                    required
                  />
                  {formErrors.email && <span className="error-text">{formErrors.email}</span>}
                </div>
                <div className="form-group">
                  <label>Téléphone <span className="required">*</span></label>
                  <input
                    type="tel"
                    value={formData.phone}
                    onChange={(e) => setFormData({...formData, phone: e.target.value})}
                    placeholder="+243 XX XXX XXXX"
                    required
                  />
                  {formErrors.phone && <span className="error-text">{formErrors.phone}</span>}
                </div>
              </div>

              {!editingUser && (
                <div className="form-group">
                  <label>Mot de passe <span className="required">*</span></label>
                  <input
                    type="password"
                    value={formData.password}
                    onChange={(e) => setFormData({...formData, password: e.target.value})}
                    placeholder="Minimum 8 caractères"
                    required={!editingUser}
                    minLength={8}
                  />
                  {formErrors.password && <span className="error-text">{formErrors.password}</span>}
                  <small className="help-text">
                    💡 Un mot de passe par défaut sera utilisé si non spécifié
                  </small>
                </div>
              )}

              <div className="form-group">
                <label>Profil d'accès <span className="required">*</span></label>
                <select
                  value={formData.profileId}
                  onChange={(e) => {
                    const profileId = e.target.value;
                    setFormData({
                      ...formData, 
                      profileId: profileId,
                      role: profileId as User['role'] 
                    });
                  }}
                  required
                >
                  <option value="">Sélectionner un profil...</option>
                  {availableRoles.map(role => (
                    <option key={role.slug} value={role.slug}>
                      {role.name} - {role.description}
                    </option>
                  ))}
                </select>
                {formErrors.role && <span className="error-text">{formErrors.role}</span>}
                <small className="help-text">
                  💡 Les profils définissent les permissions. Gérez-les dans <strong>Paramètres → Profils & Permissions</strong>
                </small>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Département</label>
                  <input
                    type="text"
                    value={formData.department}
                    onChange={(e) => setFormData({...formData, department: e.target.value})}
                    placeholder="Ex: Mathématiques, Administration..."
                  />
                </div>
                <div className="form-group">
                  <label>Statut <span className="required">*</span></label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({...formData, status: e.target.value as User['status']})}
                    required
                  >
                    {Object.entries(statusLabels).map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="form-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setShowUserForm(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingUser ? 'Enregistrer' : 'Créer l\'utilisateur'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

(UserManagement as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default UserManagement;
