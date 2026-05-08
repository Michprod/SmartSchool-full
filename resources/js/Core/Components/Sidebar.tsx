import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import './Sidebar.css';

// types will eventually be imported or defined, we use any for now or inline it
type UserRole = string;

interface SidebarProps {
  isOpen: boolean;
  toggleSidebar: () => void;
}

interface MenuItem {
  id: string;
  label: string;
  icon: string;
  permission?: string;
}

const menuItems: MenuItem[] = [
  { id: 'dashboard', label: 'Tableau de Bord', icon: '🏠' },
  { id: 'finance', label: 'Gestion Financière', icon: '💰', permission: 'finance:read' },
  { id: 'students', label: 'Gestion Élèves', icon: '👥', permission: 'students:read' },
  { id: 'admissions', label: 'Inscriptions', icon: '📝', permission: 'admissions:read' },
  { id: 'grades', label: 'Notes & Bulletins', icon: '🏫', permission: 'grades:read' },
  { id: 'communication', label: 'Communication', icon: '📢', permission: 'communication:read' },
  { id: 'events', label: 'Événements', icon: '🎉', permission: 'events:read' },
  { id: 'inventory', label: 'Inventaire', icon: '📦', permission: 'inventory:read' },
  { id: 'users', label: 'Utilisateurs', icon: '👤', permission: 'users:read' },
  { id: 'reports', label: 'Rapports', icon: '📊', permission: 'reports:read' },
  { id: 'profile', label: 'Mon Profil', icon: '👤' },
  { id: 'settings', label: 'Paramètres', icon: '⚙️', permission: 'settings:read' }
];

const Sidebar: React.FC<SidebarProps> = ({ isOpen, toggleSidebar }) => {
  const { url, props } = usePage<any>();
  const user = props.auth.user;
  const userRole = user?.role || 'admin';
  const activePage = url.split('/')[1] || 'dashboard';

  const checkPermission = (permission?: string) => {
    if (!permission) return true;
    if (userRole === 'admin') return true;
    
    const perms = user?.all_permissions || [];
    if (perms.includes('*')) return true;
    if (perms.includes(permission)) return true;
    
    const [resource] = permission.split(':');
    if (perms.includes(`${resource}:*`)) return true;
    
    return false;
  };

  const filteredMenuItems = menuItems.filter(item => checkPermission(item.permission));

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