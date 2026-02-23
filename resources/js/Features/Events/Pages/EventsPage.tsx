import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { SchoolEvent } from '../types';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './EventsPage.css';

const EventsPage: React.FC = () => {
  const [events, setEvents] = useState<SchoolEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedFilter, setSelectedFilter] = useState<'all' | 'upcoming' | 'past'>('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedEvent, setSelectedEvent] = useState<SchoolEvent | null>(null);
  const [showEventModal, setShowEventModal] = useState(false);

  const [newEvent, setNewEvent] = useState<Omit<SchoolEvent, 'id'>>({
    title: '',
    description: '',
    date: new Date(),
    location: '',
    organizer: '',
    media: []
  });

  useEffect(() => {
    const loadEvents = async () => {
      setTimeout(() => {
        const mockEvents: SchoolEvent[] = [
          {
            id: '1',
            title: 'Réunion des Parents - 6ème Année',
            description: 'Réunion trimestrielle pour discuter des progrès des élèves de 6ème année et planifier les activités du prochain trimestre.',
            date: new Date('2024-02-15T14:00:00'),
            location: 'Salle de conférence principale',
            organizer: 'Direction Pédagogique'
          },
          {
            id: '2',
            title: 'Journée Portes Ouvertes',
            description: 'Découvrez notre école ! Visite des installations, rencontre avec les enseignants et présentation des programmes.',
            date: new Date('2024-02-20T09:00:00'),
            location: 'Campus principal',
            organizer: 'Service des Admissions',
            media: [
              {
                type: 'image',
                url: 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=400',
                name: 'campus-overview.jpg',
                size: 245760
              },
              {
                type: 'document',
                url: '#',
                name: 'programme-portes-ouvertes.pdf',
                size: 1048576
              }
            ]
          },
          {
            id: '3',
            title: 'Concours de Sciences',
            description: 'Compétition inter-classes de sciences naturelles. Les élèves présenteront leurs projets scientifiques.',
            date: new Date('2024-02-25T10:00:00'),
            location: 'Laboratoire de Sciences',
            organizer: 'Département Sciences'
          },
          {
            id: '4',
            title: 'Spectacle de Fin d\'Année',
            description: 'Représentation théâtrale et musicale préparée par les élèves de toutes les classes.',
            date: new Date('2024-06-15T18:00:00'),
            location: 'Auditorium',
            organizer: 'Département Arts'
          },
          {
            id: '5',
            title: 'Formation Premiers Secours - Personnel',
            description: 'Session de formation aux premiers secours pour tout le personnel enseignant et administratif.',
            date: new Date('2024-01-20T08:00:00'),
            location: 'Salle de formation',
            organizer: 'Ressources Humaines'
          }
        ];
        setEvents(mockEvents);
        setLoading(false);
      }, 1000);
    };

    loadEvents();
  }, []);

  const filteredEvents = events.filter(event => {
    const matchesSearch = event.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.location.toLowerCase().includes(searchTerm.toLowerCase());
    
    const now = new Date();
    const matchesFilter = selectedFilter === 'all' || 
                         (selectedFilter === 'upcoming' && event.date > now) ||
                         (selectedFilter === 'past' && event.date <= now);
    
    return matchesSearch && matchesFilter;
  });

  const upcomingEvents = events.filter(event => event.date > new Date());
  const pastEvents = events.filter(event => event.date <= new Date());

  const handleCreateEvent = () => {
    const eventToCreate: SchoolEvent = {
      ...newEvent,
      id: Date.now().toString()
    };
    
    setEvents(prev => [...prev, eventToCreate]);
    setNewEvent({
      title: '',
      description: '',
      date: new Date(),
      location: '',
      organizer: '',
      media: []
    });
    setShowCreateModal(false);
  };

  const handleDeleteEvent = (eventId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
      setEvents(prev => prev.filter(event => event.id !== eventId));
    }
  };

  const handleMediaUpload = (files: FileList | null) => {
    if (!files) return;
    
    const newMediaFiles = Array.from(files).map(file => {
      const fileType = file.type.startsWith('image/') ? 'image' : 
                      file.type.startsWith('video/') ? 'video' : 'document';
      
      return {
        type: fileType as 'image' | 'video' | 'document',
        url: URL.createObjectURL(file),
        name: file.name,
        size: file.size
      };
    });
    
    setNewEvent(prev => ({
      ...prev,
      media: [...(prev.media || []), ...newMediaFiles]
    }));
  };

  const handleRemoveMedia = (index: number) => {
    setNewEvent(prev => ({
      ...prev,
      media: prev.media?.filter((_, i) => i !== index) || []
    }));
  };

  const getMediaIcon = (type: string) => {
    switch (type) {
      case 'image': return '🖼️';
      case 'video': return '🎥';
      case 'document': return '📄';
      default: return '📎';
    }
  };

  const formatFileSize = (bytes?: number) => {
    if (!bytes) return '';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  };

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const formatDateShort = (date: Date) => {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const isUpcoming = (date: Date) => date > new Date();

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement des événements...</p>
      </div>
    );
  }

  return (
    <div className="events-page">
      <Head title="Gestion des Événements" />
      <div className="page-header">
        <h1>Gestion des Événements</h1>
        <p className="page-subtitle">
          Planification et suivi des activités scolaires
        </p>
      </div>

      {/* Statistiques rapides */}
      <div className="events-stats">
        <div className="stat-card total">
          <div className="stat-icon">📅</div>
          <div className="stat-content">
            <span className="stat-number">{events.length}</span>
            <span className="stat-label">Total Événements</span>
          </div>
        </div>
        <div className="stat-card upcoming">
          <div className="stat-icon">⏰</div>
          <div className="stat-content">
            <span className="stat-number">{upcomingEvents.length}</span>
            <span className="stat-label">À Venir</span>
          </div>
        </div>
        <div className="stat-card past">
          <div className="stat-icon">✅</div>
          <div className="stat-content">
            <span className="stat-number">{pastEvents.length}</span>
            <span className="stat-label">Terminés</span>
          </div>
        </div>
      </div>

      {/* Contrôles de gestion */}
      <div className="management-controls">
        <div className="search-filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Rechercher un événement..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
          
          <div className="filter-group">
            <label>Filtrer :</label>
            <select
              value={selectedFilter}
              onChange={(e) => setSelectedFilter(e.target.value as 'all' | 'upcoming' | 'past')}
              className="filter-select"
            >
              <option value="all">Tous les événements</option>
              <option value="upcoming">À venir</option>
              <option value="past">Terminés</option>
            </select>
          </div>
        </div>
        
        <div className="action-buttons">
          <button className="btn btn-outline">
            <span>📊</span>
            Exporter
          </button>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Nouvel Événement
          </button>
        </div>
      </div>

      {/* Liste des événements */}
      <div className="events-grid">
        {filteredEvents.map(event => (
          <div 
            key={event.id} 
            className={`event-card ${isUpcoming(event.date) ? 'upcoming' : 'past'}`}
          >
            <div className="event-header">
              <div className="event-date">
                <div className="date-day">
                  {event.date.getDate()}
                </div>
                <div className="date-month">
                  {event.date.toLocaleDateString('fr-FR', { month: 'short' })}
                </div>
              </div>
              <div className="event-status">
                <span className={`status-badge ${isUpcoming(event.date) ? 'upcoming' : 'past'}`}>
                  {isUpcoming(event.date) ? 'À venir' : 'Terminé'}
                </span>
              </div>
            </div>
            
            <div className="event-content">
              <h3 className="event-title">{event.title}</h3>
              <p className="event-description">{event.description}</p>
              
              <div className="event-details">
                <div className="detail-item">
                  <span className="detail-icon">🕒</span>
                  <span className="detail-text">{formatDateShort(event.date)}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-icon">📍</span>
                  <span className="detail-text">{event.location}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-icon">👤</span>
                  <span className="detail-text">{event.organizer}</span>
                </div>
              </div>
            </div>
            
            <div className="event-actions">
              <button 
                className="action-btn primary"
                onClick={() => {
                  setSelectedEvent(event);
                  setShowEventModal(true);
                }}
              >
                <span>👁️</span>
                Voir
              </button>
              <button className="action-btn secondary">
                <span>✏️</span>
                Modifier
              </button>
              <button 
                className="action-btn danger"
                onClick={() => handleDeleteEvent(event.id)}
              >
                <span>🗑️</span>
                Supprimer
              </button>
            </div>
          </div>
        ))}
      </div>

      {filteredEvents.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">📅</div>
          <h3>Aucun événement trouvé</h3>
          <p>Aucun événement ne correspond à vos critères de recherche.</p>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Créer un événement
          </button>
        </div>
      )}

      {/* Modal de création d'événement */}
      {showCreateModal && (
        <div className="modal-overlay" onClick={() => setShowCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Créer un Nouvel Événement</h2>
              <button 
                className="modal-close"
                onClick={() => setShowCreateModal(false)}
              >
                ✕
              </button>
            </div>
            
            <form onSubmit={(e) => {
              e.preventDefault();
              handleCreateEvent();
            }}>
              <div className="form-group">
                <label>Titre de l'événement *</label>
                <input
                  type="text"
                  value={newEvent.title}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, title: e.target.value }))}
                  placeholder="Ex: Réunion des parents"
                  required
                />
              </div>
              
              <div className="form-group">
                <label>Description *</label>
                <textarea
                  value={newEvent.description}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, description: e.target.value }))}
                  placeholder="Décrivez l'événement..."
                  rows={4}
                  required
                />
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label>Date et heure *</label>
                  <input
                    type="datetime-local"
                    value={newEvent.date.toISOString().slice(0, 16)}
                    onChange={(e) => setNewEvent(prev => ({ ...prev, date: new Date(e.target.value) }))}
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label>Lieu *</label>
                  <input
                    type="text"
                    value={newEvent.location}
                    onChange={(e) => setNewEvent(prev => ({ ...prev, location: e.target.value }))}
                    placeholder="Ex: Salle de conférence"
                    required
                  />
                </div>
              </div>
              
              <div className="form-group">
                <label>Organisateur *</label>
                <input
                  type="text"
                  value={newEvent.organizer}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, organizer: e.target.value }))}
                  placeholder="Ex: Direction Pédagogique"
                  required
                />
              </div>
              
              {/* Section Média (optionnelle) */}
              <div className="form-group">
                <label>Médias (optionnel)</label>
                <div className="media-upload-section">
                  <input
                    type="file"
                    id="media-upload"
                    multiple
                    accept="image/*,video/*,.pdf,.doc,.docx"
                    onChange={(e) => handleMediaUpload(e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="media-upload" className="upload-button">
                    <span>📎</span>
                    Ajouter des fichiers
                  </label>
                  <span className="upload-hint">Images, vidéos ou documents</span>
                </div>
                
                {/* Aperçu des médias */}
                {newEvent.media && newEvent.media.length > 0 && (
                  <div className="media-preview">
                    <h4>Fichiers attachés :</h4>
                    <div className="media-list">
                      {newEvent.media.map((media, index) => (
                        <div key={index} className="media-item">
                          <div className="media-info">
                            <span className="media-icon">{getMediaIcon(media.type)}</span>
                            <div className="media-details">
                              <span className="media-name">{media.name}</span>
                              <span className="media-size">{formatFileSize(media.size)}</span>
                            </div>
                          </div>
                          <button
                            type="button"
                            className="remove-media"
                            onClick={() => handleRemoveMedia(index)}
                            title="Supprimer ce fichier"
                          >
                            ✕
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
              
              <div className="modal-actions">
                <button 
                  type="button" 
                  className="btn btn-outline"
                  onClick={() => setShowCreateModal(false)}
                >
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  Créer l'Événement
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de détails d'événement */}
      {showEventModal && selectedEvent && (
        <div className="modal-overlay" onClick={() => setShowEventModal(false)}>
          <div className="modal-content event-details-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{selectedEvent.title}</h2>
              <button 
                className="modal-close"
                onClick={() => setShowEventModal(false)}
              >
                ✕
              </button>
            </div>
            
            <div className="event-details-content">
              <div className="event-meta">
                <span className={`status-badge ${isUpcoming(selectedEvent.date) ? 'upcoming' : 'past'}`}>
                  {isUpcoming(selectedEvent.date) ? 'À venir' : 'Terminé'}
                </span>
              </div>
              
              <div className="event-info">
                <div className="info-item">
                  <span className="info-icon">📅</span>
                  <div className="info-content">
                    <span className="info-label">Date et heure</span>
                    <span className="info-value">{formatDate(selectedEvent.date)}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📍</span>
                  <div className="info-content">
                    <span className="info-label">Lieu</span>
                    <span className="info-value">{selectedEvent.location}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">👤</span>
                  <div className="info-content">
                    <span className="info-label">Organisateur</span>
                    <span className="info-value">{selectedEvent.organizer}</span>
                  </div>
                </div>
              </div>
              
              <div className="event-description-full">
                <h4>Description</h4>
                <p>{selectedEvent.description}</p>
              </div>
              
              {/* Section Médias */}
              {selectedEvent.media && selectedEvent.media.length > 0 && (
                <div className="event-media-section">
                  <h4>Médias attachés</h4>
                  <div className="media-grid">
                    {selectedEvent.media.map((media, index) => (
                      <div key={index} className="media-card">
                        <div className="media-preview">
                          {media.type === 'image' ? (
                            <img 
                              src={media.url} 
                              alt={media.name}
                              className="media-thumbnail"
                            />
                          ) : (
                            <div className="media-placeholder">
                              <span className="media-icon-large">{getMediaIcon(media.type)}</span>
                            </div>
                          )}
                        </div>
                        <div className="media-info">
                          <span className="media-name" title={media.name}>{media.name}</span>
                          <span className="media-size">{formatFileSize(media.size)}</span>
                          <button 
                            className="btn btn-sm btn-outline"
                            onClick={() => window.open(media.url, '_blank')}
                          >
                            <span>🔗</span>
                            Ouvrir
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
            
            <div className="modal-actions">
              <button className="btn btn-outline">
                <span>✏️</span>
                Modifier
              </button>
              <button 
                className="btn btn-danger"
                onClick={() => {
                  handleDeleteEvent(selectedEvent.id);
                  setShowEventModal(false);
                }}
              >
                <span>🗑️</span>
                Supprimer
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

(EventsPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default EventsPage;