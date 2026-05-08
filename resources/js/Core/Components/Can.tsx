import React from 'react';
import { usePage } from '@inertiajs/react';

interface CanProps {
  permission?: string;
  permissions?: string[];
  role?: string;
  roles?: string[];
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

const Can: React.FC<CanProps> = ({ 
  permission, 
  permissions, 
  role, 
  roles, 
  children, 
  fallback = null 
}) => {
  const { auth } = usePage<any>().props;
  const user = auth.user;

  if (!user) return <>{fallback}</>;

  const userPermissions = user.all_permissions || [];
  const userRole = user.role;

  let hasAccess = false;

  // Check if admin (usually has all access)
  if (userRole === 'admin' || userPermissions.includes('*')) {
    hasAccess = true;
  } else {
    // Check role
    if (role && userRole === role) hasAccess = true;
    if (roles && roles.includes(userRole)) hasAccess = true;

    // Check permission
    if (permission) {
      if (userPermissions.includes(permission)) hasAccess = true;
      
      // Check wildcard resource permission (e.g., 'students:*' matches 'students:read')
      const [resource] = permission.split(':');
      if (userPermissions.includes(`${resource}:*`)) hasAccess = true;
    }

    // Check multiple permissions
    if (permissions) {
      if (permissions.some(p => {
        if (userPermissions.includes(p)) return true;
        const [res] = p.split(':');
        return userPermissions.includes(`${res}:*`);
      })) {
        hasAccess = true;
      }
    }
  }

  return hasAccess ? <>{children}</> : <>{fallback}</>;
};

export default Can;
