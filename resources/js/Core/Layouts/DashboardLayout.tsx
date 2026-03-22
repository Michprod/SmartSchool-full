import React, { useState } from 'react';
import Sidebar from '../Components/Sidebar';
import Header from '../Components/Header';
import './DashboardLayout.css';

const DashboardLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [sidebarOpen, setSidebarOpen] = useState(window.innerWidth > 768);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  React.useEffect(() => {
    const handleResize = () => {
      const mobile = window.innerWidth <= 768;
      setIsMobile(mobile);
      if (!mobile) {
        setSidebarOpen(true);
      } else {
        setSidebarOpen(false);
      }
    };

    window.addEventListener('resize', handleResize);
    
    // Load html2pdf.js for receipt generation
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    script.async = true;
    document.body.appendChild(script);

    return () => {
      window.removeEventListener('resize', handleResize);
      document.body.removeChild(script);
    };
  }, []);

  return (
    <div className={`dashboard-container ${isMobile ? 'mobile-view' : ''}`}>
      <Sidebar
        isOpen={sidebarOpen}
        toggleSidebar={() => setSidebarOpen(!sidebarOpen)}
      />
      <div className={`main-content ${sidebarOpen ? 'sidebar-open' : 'sidebar-closed'}`}>
        <Header
          toggleSidebar={() => setSidebarOpen(!sidebarOpen)}
        />
        <main className="page-content">
          {children}
        </main>
      </div>
      {isMobile && sidebarOpen && <div className="mobile-overlay" onClick={() => setSidebarOpen(false)}></div>}
    </div>
  );
};

export default DashboardLayout;