import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import './Sidebar.css';

// types will eventually be imported or defined, we use any for now or inline it
type UserRole = 'admin' | 'teacher' | 'parent' | 'student';

interface SidebarProps {
  isOpen: boolean;
  toggleSidebar: () => void;
}

interface MenuItem {
  id: string;
  label: string;
  icon: string;
  roles: UserRole[];
}

const menuItems: MenuItem[] = [
  { id: 'dashboard', label: 'Tableau de Bord', icon: '🏠', roles: ['admin', 'teacher', 'parent', 'student'] },
  { id: 'finance', label: 'Gestion Financière', icon: '💰', roles: ['admin'] },
  { id: 'students', label: 'Gestion Élèves', icon: '👥', roles: ['admin', 'teacher'] },
  { id: 'admissions', label: 'Inscriptions', icon: '📝', roles: ['admin'] },
  { id: 'communication', label: 'Communication', icon: '📢', roles: ['admin', 'teacher'] },
  { id: 'events', label: 'Événements', icon: '🎉', roles: ['admin', 'teacher'] },
  { id: 'inventory', label: 'Inventaire', icon: '📦', roles: ['admin'] },
  { id: 'users', label: 'Utilisateurs', icon: '👤', roles: ['admin'] },
  { id: 'reports', label: 'Rapports', icon: '📊', roles: ['admin', 'teacher'] },
  { id: 'profile', label: 'Mon Profil', icon: '👤', roles: ['admin', 'teacher', 'parent', 'student'] },
  { id: 'settings', label: 'Paramètres', icon: '⚙️', roles: ['admin'] }
];

const Sidebar: React.FC<SidebarProps> = ({ isOpen, toggleSidebar }) => {
  const { url, props } = usePage<any>();
  const user = props.auth.user;
  const userRole = user?.role || 'admin';
  const activePage = url.split('/')[1] || 'dashboard';

  const filteredMenuItems = menuItems.filter(item => item.roles.includes(userRole));

  return (
    <>
      <aside className={`sidebar ${isOpen ? 'open' : 'closed'}`}>
        <div className="sidebar-header">
          <div className="logo">
            <span className="logo-icon">🎓</span>
            {isOpen && <span className="logo-text">SmartSchool RDC</span>}
          </div>
        </div>
        
        <nav className="sidebar-nav">
        <ul className="nav-list">
          {filteredMenuItems.map((item) => (
            <li key={item.id} className="nav-item">
              <Link
                href={`/${item.id}`}
                className={`nav-link ${activePage === item.id ? 'active' : ''}`}
                title={!isOpen ? item.label : undefined}
              >
                <span className="nav-icon">{item.icon}</span>
                {isOpen && <span className="nav-label">{item.label}</span>}
              </Link>
            </li>
          ))}
        </ul>
      </nav>
      
      <div className="sidebar-footer">
        <div className="user-info">
          <div className="user-avatar">👤</div>
          {isOpen && (
            <div className="user-details">
              <p className="user-role">{typeof userRole === 'string' ? userRole.charAt(0).toUpperCase() + userRole.slice(1) : userRole}</p>
              <p className="user-status">En ligne</p>
            </div>
          )}
        </div>
      </div>
      </aside>
      {isOpen && <div className="mobile-overlay" onClick={toggleSidebar}></div>}
    </>
  );
};

export default Sidebar;