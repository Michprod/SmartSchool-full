import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { Notification } from '../types';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './CommunicationCenter.css';
import axios from 'axios';

const CommunicationCenter: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'send' | 'history' | 'templates'>('send');
  const [newNotification, setNewNotification] = useState({
    title: '',
    message: '',
    type: 'info' as 'info' | 'warning' | 'success' | 'error',
    recipients: 'all' as 'all' | 'parents' | 'teachers' | 'students',
    channels: ['push'] as ('push' | 'sms' | 'email')[]
  });

  const loadNotifications = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/announcements');
      const data = response.data?.data || response.data || [];
      const mappedNotifications = (Array.isArray(data) ? data : []).map((n: any) => ({
        id: n.id.toString(),
        title: n.title,
        message: n.content,
        type: n.type === 'urgent' ? 'error' : n.type,
        recipients: n.target_audience === 'all' ? ['Tous'] : [n.target_audience],
        sentAt: new Date(n.sent_at || n.created_at),
        readBy: [], 
        channels: ['push'] as ('push' | 'sms' | 'email')[]
      }));
      setNotifications(mappedNotifications);
    } catch (error) {
      console.error('Error loading communication data:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadNotifications();
  }, []);

  const handleSendNotification = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const payload = {
        title: newNotification.title,
        message: newNotification.message,
        type: newNotification.type,
        channels: newNotification.channels,
        recipients: [newNotification.recipients]
      };
      
      const response = await axios.post('/api/announcements', payload);
      const n = response.data;
      
      const notification: Notification = {
        id: n.id.toString(),
        title: n.title,
        message: n.message,
        type: n.type,
        recipients: n.recipients || [],
        sentAt: new Date(n.created_at),
        readBy: [],
        channels: newNotification.channels
      };
      
      setNotifications(prev => [notification, ...prev]);
      
      setNewNotification({
        title: '',
        message: '',
        type: 'info',
        recipients: 'all',
        channels: ['push']
      });
      
      alert('Notification envoyée avec succès!');
    } catch (e: any) {
      console.error('Erreur lors de l\'envoi:', e);
      alert("Erreur lors de l'envoi de la notification.");
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'info': return 'ℹ️';
      case 'warning': return '⚠️';
      case 'success': return '✅';
      case 'error': return '❌';
      default: return 'ℹ️';
    }
  };

  const getChannelIcon = (channel: string) => {
    switch (channel) {
      case 'push': return '📱';
      case 'sms': return '💬';
      case 'email': return '📧';
      default: return '📱';
    }
  };

  const messageTemplates = [
    {
      id: '1',
      name: 'Rappel de paiement',
      category: 'Finance',
      title: 'Rappel de paiement - {studentName}',
      message: 'Cher parent, votre enfant {studentName} a un paiement en attente. Montant: {amount} {currency}. Échéance: {dueDate}.'
    },
    {
      id: '2',
      name: 'Réunion parents',
      category: 'Événement',
      title: 'Réunion parents-enseignants',
      message: 'Une réunion parents-enseignants aura lieu le {date} à {time}. Votre présence est importante pour le suivi de {studentName}.'
    },
    {
      id: '3',
      name: 'Absence élève',
      category: 'Académique',
      title: 'Absence de {studentName}',
      message: 'Votre enfant {studentName} a été absent le {date}. Merci de nous informer de la raison de cette absence.'
    }
  ];

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement du centre de communication...</p>
      </div>
    );
  }

  return (
    <div className="communication-center">
      <Head title="Centre de Communication" />
      <div className="page-header">
        <h1>Centre de Communication</h1>
        <p className="page-subtitle">
          Système de notifications multi-canal - SMS, Push, Email
        </p>
      </div>

      {/* Statistiques de communication */}
      <div className="communication-stats">
        <div className="stat-card sent">
          <span className="stat-icon">📤</span>
          <div className="stat-content">
            <h3>Envoyées</h3>
            <span className="stat-number">{notifications.length}</span>
            <span className="stat-label">Ce mois</span>
          </div>
        </div>
        
        <div className="stat-card delivered">
          <span className="stat-icon">✅</span>
          <div className="stat-content">
            <h3>Délivrées</h3>
            <span className="stat-number">{notifications.length}</span>
            <span className="stat-label">Taux: 100%</span>
          </div>
        </div>
        
        <div className="stat-card read">
          <span className="stat-icon">👁️</span>
          <div className="stat-content">
            <h3>Lues</h3>
            <span className="stat-number">{notifications.filter(n => n.readBy.length > 0).length}</span>
            <span className="stat-label">Taux: {Math.round((notifications.filter(n => n.readBy.length > 0).length / notifications.length) * 100)}%</span>
          </div>
        </div>
        
        <div className="stat-card channels">
          <span className="stat-icon">📱</span>
          <div className="stat-content">
            <h3>Canaux</h3>
            <span className="stat-number">3</span>
            <span className="stat-label">Push, SMS, Email</span>
          </div>
        </div>
      </div>

      {/* Navigation par onglets */}
      <div className="tabs-navigation">
        <button 
          className={`tab-btn ${activeTab === 'send' ? 'active' : ''}`}
          onClick={() => setActiveTab('send')}
        >
          <span className="tab-icon">📤</span>
          Envoyer
        </button>
        <button 
          className={`tab-btn ${activeTab === 'history' ? 'active' : ''}`}
          onClick={() => setActiveTab('history')}
        >
          <span className="tab-icon">📋</span>
          Historique
        </button>
        <button 
          className={`tab-btn ${activeTab === 'templates' ? 'active' : ''}`}
          onClick={() => setActiveTab('templates')}
        >
          <span className="tab-icon">📝</span>
          Modèles
        </button>
      </div>

      {/* Onglet Envoyer */}
      {activeTab === 'send' && (
        <div className="send-notification-content">
          <div className="send-form-container">
            <h2>Nouvelle notification</h2>
            
            <form onSubmit={handleSendNotification} className="notification-form">
              <div className="form-row">
                <div className="form-group">
                  <label>Titre</label>
                  <input
                    type="text"
                    value={newNotification.title}
                    onChange={(e) => setNewNotification(prev => ({ ...prev, title: e.target.value }))}
                    placeholder="Titre de la notification"
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label>Type</label>
                  <select
                    value={newNotification.type}
                    onChange={(e) => setNewNotification(prev => ({ ...prev, type: e.target.value as any }))}
                  >
                    <option value="info">Information</option>
                    <option value="warning">Avertissement</option>
                    <option value="success">Succès</option>
                    <option value="error">Erreur</option>
                  </select>
                </div>
              </div>
              
              <div className="form-group">
                <label>Message</label>
                <textarea
                  value={newNotification.message}
                  onChange={(e) => setNewNotification(prev => ({ ...prev, message: e.target.value }))}
                  placeholder="Contenu de la notification..."
                  rows={4}
                  required
                />
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label>Destinataires</label>
                  <select
                    value={newNotification.recipients}
                    onChange={(e) => setNewNotification(prev => ({ ...prev, recipients: e.target.value as any }))}
                  >
                    <option value="all">Tous les utilisateurs</option>
                    <option value="parents">Parents uniquement</option>
                    <option value="teachers">Enseignants uniquement</option>
                    <option value="students">Élèves uniquement</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Canaux de diffusion</label>
                  <div className="channels-selection">
                    <label className="channel-option">
                      <input
                        type="checkbox"
                        checked={newNotification.channels.includes('push')}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: [...prev.channels, 'push']
                            }));
                          } else {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: prev.channels.filter(c => c !== 'push')
                            }));
                          }
                        }}
                      />
                      <span className="channel-icon">📱</span>
                      Push
                    </label>
                    
                    <label className="channel-option">
                      <input
                        type="checkbox"
                        checked={newNotification.channels.includes('sms')}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: [...prev.channels, 'sms']
                            }));
                          } else {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: prev.channels.filter(c => c !== 'sms')
                            }));
                          }
                        }}
                      />
                      <span className="channel-icon">💬</span>
                      SMS
                    </label>
                    
                    <label className="channel-option">
                      <input
                        type="checkbox"
                        checked={newNotification.channels.includes('email')}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: [...prev.channels, 'email']
                            }));
                          } else {
                            setNewNotification(prev => ({ 
                              ...prev, 
                              channels: prev.channels.filter(c => c !== 'email')
                            }));
                          }
                        }}
                      />
                      <span className="channel-icon">📧</span>
                      Email
                    </label>
                  </div>
                </div>
              </div>
              
              <div className="form-actions">
                <button type="button" className="btn btn-outline">
                  <span>💾</span>
                  Sauver comme modèle
                </button>
                <button type="submit" className="btn btn-primary">
                  <span>📤</span>
                  Envoyer notification
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Onglet Historique */}
      {activeTab === 'history' && (
        <div className="history-content">
          <div className="notifications-list">
            {notifications.map(notification => (
              <div key={notification.id} className="notification-card">
                <div className="notification-header">
                  <div className="notification-info">
                    <span className="notification-icon">
                      {getNotificationIcon(notification.type)}
                    </span>
                    <div className="notification-details">
                      <h3 className="notification-title">{notification.title}</h3>
                      <p className="notification-meta">
                        Envoyée le {notification.sentAt.toLocaleDateString()} à {notification.sentAt.toLocaleTimeString()}
                      </p>
                    </div>
                  </div>
                  
                  <div className="notification-channels">
                    {notification.channels.map(channel => (
                      <span key={channel} className="channel-badge" title={channel}>
                        {getChannelIcon(channel)}
                      </span>
                    ))}
                  </div>
                </div>
                
                <div className="notification-content">
                  <p className="notification-message">{notification.message}</p>
                </div>
                
                <div className="notification-stats">
                  <div className="stat-item">
                    <span className="stat-label">Destinataires:</span>
                    <span className="stat-value">{notification.recipients.length}</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-label">Lue par:</span>
                    <span className="stat-value">{notification.readBy.length}</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-label">Taux de lecture:</span>
                    <span className="stat-value">
                      {Math.round((notification.readBy.length / notification.recipients.length) * 100)}%
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Onglet Modèles */}
      {activeTab === 'templates' && (
        <div className="templates-content">
          <div className="templates-header">
            <h2>Modèles de messages</h2>
            <button className="btn btn-primary">
              <span>➕</span>
              Nouveau modèle
            </button>
          </div>
          
          <div className="templates-grid">
            {messageTemplates.map(template => (
              <div key={template.id} className="template-card">
                <div className="template-header">
                  <h3 className="template-name">{template.name}</h3>
                  <span className="template-category">{template.category}</span>
                </div>
                
                <div className="template-content">
                  <h4 className="template-title">{template.title}</h4>
                  <p className="template-message">{template.message}</p>
                </div>
                
                <div className="template-actions">
                  <button className="action-btn primary">
                    <span>📝</span>
                    Utiliser
                  </button>
                  <button className="action-btn secondary">
                    <span>✏️</span>
                    Modifier
                  </button>
                  <button className="action-btn secondary">
                    <span>🗑️</span>
                    Supprimer
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

(CommunicationCenter as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default CommunicationCenter;