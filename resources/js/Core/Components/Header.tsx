import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import './Header.css';

interface HeaderProps {
  toggleSidebar: () => void;
}

const Header: React.FC<HeaderProps> = ({ toggleSidebar }) => {
  const { props } = usePage<any>();
  const user = props.auth.user;

  const [showUserMenu, setShowUserMenu] = useState(false);
  const [notifications] = useState([
    { id: 1, title: 'Nouveau paiement reçu', type: 'success' },
    { id: 2, title: '5 nouvelles inscriptions', type: 'info' },
    { id: 3, title: 'Rappel: Réunion parents', type: 'warning' }
  ]);

  const formatUserName = (u: any) => {
    if (!u) return 'Utilisateur';
    // Laravel user usually has 'name', but old user object had 'firstName', 'lastName'. 
    // Let's use name if available, otherwise combine.
    if (u.name) return u.name;
    return `${u.firstName || ''} ${u.lastName || ''}`.trim() || 'Utilisateur';
  };

  const getRoleLabel = (role: string) => {
    const roleLabels = {
      admin: 'Administrateur',
      teacher: 'Enseignant',
      parent: 'Parent',
      student: 'Élève'
    };
    return roleLabels[role as keyof typeof roleLabels] || role;
  };

  return (
    <header className="header">
      <div className="header-left">
        <button 
          className="sidebar-toggle"
          onClick={toggleSidebar}
          aria-label="Toggle sidebar"
        >
          ☰
        </button>
        
        <div className="header-title">
          <h1>Tableau de Bord Administratif</h1>
          <p className="header-subtitle">smartSchool RDC - Gestion Scolaire Moderne</p>
        </div>
      </div>
      
      <div className="header-right">
        {/* Notifications */}
        <div className="notifications">
          <button className="notification-btn">
            <span className="notification-icon">🔔</span>
            {notifications.length > 0 && (
              <span className="notification-badge">{notifications.length}</span>
            )}
          </button>
        </div>
        
        {/* Currency Display */}
        <div className="currency-display">
          <div className="currency-item">
            <span className="currency-label">CDF</span>
            <span className="currency-rate">2,750</span>
          </div>
          <div className="currency-item">
            <span className="currency-label">USD</span>
            <span className="currency-rate">1.00</span>
          </div>
        </div>
        
        {/* User Menu */}
        <div className="user-menu-container">
          <button 
            className="user-menu-btn"
            onClick={() => setShowUserMenu(!showUserMenu)}
          >
            <div className="user-avatar">
              {(user?.name || user?.firstName || 'U').charAt(0).toUpperCase()}
            </div>
            <div className="user-info">
              <span className="user-name">{formatUserName(user)}</span>
              <span className="user-role">{getRoleLabel(user?.role || 'admin')}</span>
            </div>
            <span className="dropdown-arrow">▼</span>
          </button>
          
          {showUserMenu && (
            <div className="user-dropdown">
              <div className="dropdown-header">
                <div className="user-avatar large">
                  {(user?.name || user?.firstName || 'U').charAt(0).toUpperCase()}
                </div>
                <div>
                  <p className="user-name-large">{formatUserName(user)}</p>
                  <p className="user-email">{user?.email}</p>
                  <p className="user-role-large">{getRoleLabel(user?.role || 'admin')}</p>
                </div>
              </div>
              
              <div className="dropdown-menu">
                <Link href="/profile" className="dropdown-item">
                  <span className="item-icon">👤</span>
                  Profil
                </Link>
                <Link href="/settings" className="dropdown-item">
                  <span className="item-icon">⚙️</span>
                  Paramètres
                </Link>
                <button className="dropdown-item">
                  <span className="item-icon">❓</span>
                  Aide
                </button>
                <hr className="dropdown-divider" />
                <Link 
                  href="/logout"
                  method="post"
                  as="button"
                  className="dropdown-item logout"
                >
                  <span className="item-icon">🚪</span>
                  Déconnexion
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

export default Header;