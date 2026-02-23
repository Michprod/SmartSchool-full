import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { InventoryItem } from '../types';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './InventoryPage.css';

const InventoryPage: React.FC = () => {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [selectedStatus, setSelectedStatus] = useState<string>('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);
  const [showItemModal, setShowItemModal] = useState(false);
  const [newItem, setNewItem] = useState<Partial<InventoryItem>>({
    name: '',
    category: '',
    quantity: 0,
    location: '',
    status: 'in_stock'
  });

  const categories = [
    'Mobilier',
    'Informatique',
    'Matériel Pédagogique',
    'Équipement Sportif',
    'Fournitures',
    'Électronique',
    'Livres',
    'Laboratoire'
  ];

  useEffect(() => {
    const loadInventory = async () => {
      setTimeout(() => {
        const mockItems: InventoryItem[] = [
          {
            id: '1',
            name: 'Ordinateurs Portables Dell',
            category: 'Informatique',
            quantity: 25,
            location: 'Salle Informatique A',
            status: 'in_stock'
          },
          {
            id: '2',
            name: 'Tableaux Blancs Interactifs',
            category: 'Matériel Pédagogique',
            quantity: 8,
            location: 'Salles de Classe',
            status: 'in_use'
          },
          {
            id: '3',
            name: 'Chaises d\'Élèves',
            category: 'Mobilier',
            quantity: 150,
            location: 'Entrepôt Principal',
            status: 'in_stock'
          },
          {
            id: '4',
            name: 'Projecteurs Multimédia',
            category: 'Électronique',
            quantity: 12,
            location: 'Salles de Classe',
            status: 'in_use'
          },
          {
            id: '5',
            name: 'Ballons de Football',
            category: 'Équipement Sportif',
            quantity: 20,
            location: 'Gymnase',
            status: 'in_stock'
          },
          {
            id: '6',
            name: 'Microscopes',
            category: 'Laboratoire',
            quantity: 15,
            location: 'Laboratoire Sciences',
            status: 'in_use'
          },
          {
            id: '7',
            name: 'Imprimante Laser HP',
            category: 'Informatique',
            quantity: 3,
            location: 'Bureau Administration',
            status: 'under_maintenance'
          },
          {
            id: '8',
            name: 'Manuels Mathématiques 6ème',
            category: 'Livres',
            quantity: 80,
            location: 'Bibliothèque',
            status: 'in_stock'
          },
          {
            id: '9',
            name: 'Tables de Laboratoire',
            category: 'Mobilier',
            quantity: 12,
            location: 'Laboratoire Sciences',
            status: 'in_use'
          },
          {
            id: '10',
            name: 'Calculatrices Scientifiques',
            category: 'Fournitures',
            quantity: 45,
            location: 'Salle des Professeurs',
            status: 'in_stock'
          }
        ];
        setItems(mockItems);
        setLoading(false);
      }, 1000);
    };

    loadInventory();
  }, []);

  const filteredItems = items.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         item.category.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         item.location.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
    const matchesStatus = selectedStatus === 'all' || item.status === selectedStatus;
    
    return matchesSearch && matchesCategory && matchesStatus;
  });

  const getStatusStats = () => {
    return {
      total: items.length,
      inStock: items.filter(item => item.status === 'in_stock').length,
      inUse: items.filter(item => item.status === 'in_use').length,
      maintenance: items.filter(item => item.status === 'under_maintenance').length,
      totalQuantity: items.reduce((sum, item) => sum + item.quantity, 0)
    };
  };

  const handleCreateItem = () => {
    if (newItem.name && newItem.category && newItem.location && newItem.quantity !== undefined) {
      const item: InventoryItem = {
        id: Date.now().toString(),
        name: newItem.name,
        category: newItem.category,
        quantity: newItem.quantity,
        location: newItem.location,
        status: (newItem.status as InventoryItem['status']) || 'in_stock'
      };
      setItems([...items, item]);
      setNewItem({
        name: '',
        category: '',
        quantity: 0,
        location: '',
        status: 'in_stock'
      });
      setShowCreateModal(false);
    }
  };

  const handleEditItem = (item: InventoryItem) => {
    setEditingItem(item);
    setNewItem(item);
    setShowCreateModal(true);
  };

  const handleUpdateItem = () => {
    if (editingItem && newItem.name && newItem.category && newItem.location && newItem.quantity !== undefined) {
      const updatedItems = items.map(item =>
        item.id === editingItem.id
          ? { ...item, ...newItem }
          : item
      );
      setItems(updatedItems);
      setEditingItem(null);
      setNewItem({
        name: '',
        category: '',
        quantity: 0,
        location: '',
        status: 'in_stock'
      });
      setShowCreateModal(false);
    }
  };

  const handleDeleteItem = (itemId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet élément de l\'inventaire ?')) {
      setItems(items.filter(item => item.id !== itemId));
    }
  };

  const getStatusLabel = (status: string) => {
    const labels = {
      'in_stock': 'En Stock',
      'in_use': 'En Utilisation',
      'under_maintenance': 'En Maintenance'
    };
    return labels[status as keyof typeof labels] || status;
  };

  const getStatusColor = (status: string) => {
    const colors = {
      'in_stock': 'success',
      'in_use': 'warning',
      'under_maintenance': 'danger'
    };
    return colors[status as keyof typeof colors] || 'default';
  };

  const stats = getStatusStats();

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement de l'inventaire...</p>
      </div>
    );
  }

  return (
    <div className="inventory-page">
      <Head title="Gestion de l'Inventaire" />
      <div className="page-header">
        <h1>Gestion de l'Inventaire</h1>
        <p className="page-subtitle">
          Suivi et gestion des équipements scolaires
        </p>
      </div>

      {/* Statistiques de l'inventaire */}
      <div className="inventory-stats">
        <div className="stat-card total">
          <div className="stat-icon">📦</div>
          <div className="stat-content">
            <span className="stat-number">{stats.total}</span>
            <span className="stat-label">Articles Total</span>
          </div>
        </div>
        <div className="stat-card in-stock">
          <div className="stat-icon">✅</div>
          <div className="stat-content">
            <span className="stat-number">{stats.inStock}</span>
            <span className="stat-label">En Stock</span>
          </div>
        </div>
        <div className="stat-card in-use">
          <div className="stat-icon">🔄</div>
          <div className="stat-content">
            <span className="stat-number">{stats.inUse}</span>
            <span className="stat-label">En Utilisation</span>
          </div>
        </div>
        <div className="stat-card maintenance">
          <div className="stat-icon">🔧</div>
          <div className="stat-content">
            <span className="stat-number">{stats.maintenance}</span>
            <span className="stat-label">En Maintenance</span>
          </div>
        </div>
        <div className="stat-card quantity">
          <div className="stat-icon">📊</div>
          <div className="stat-content">
            <span className="stat-number">{stats.totalQuantity}</span>
            <span className="stat-label">Quantité Totale</span>
          </div>
        </div>
      </div>

      {/* Contrôles de gestion */}
      <div className="management-controls">
        <div className="search-filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Rechercher un article..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
          
          <div className="filter-group">
            <label>Catégorie :</label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="filter-select"
            >
              <option value="all">Toutes les catégories</option>
              {categories.map(category => (
                <option key={category} value={category}>{category}</option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <label>Statut :</label>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="filter-select"
            >
              <option value="all">Tous les statuts</option>
              <option value="in_stock">En Stock</option>
              <option value="in_use">En Utilisation</option>
              <option value="under_maintenance">En Maintenance</option>
            </select>
          </div>
        </div>
        
        <div className="action-buttons">
          <button className="btn btn-outline">
            <span>📊</span>
            Rapport
          </button>
          <button className="btn btn-outline">
            <span>📤</span>
            Exporter
          </button>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Ajouter Article
          </button>
        </div>
      </div>

      {/* Liste des articles */}
      <div className="inventory-grid">
        {filteredItems.map(item => (
          <div key={item.id} className="inventory-card">
            <div className="item-header">
              <div className="item-category">
                <span className="category-badge">{item.category}</span>
              </div>
              <div className="item-status">
                <span className={`status-badge ${getStatusColor(item.status)}`}>
                  {getStatusLabel(item.status)}
                </span>
              </div>
            </div>
            
            <div className="item-content">
              <h3 className="item-name">{item.name}</h3>
              
              <div className="item-details">
                <div className="detail-item">
                  <span className="detail-icon">📦</span>
                  <div className="detail-content">
                    <span className="detail-label">Quantité</span>
                    <span className="detail-value">{item.quantity}</span>
                  </div>
                </div>
                
                <div className="detail-item">
                  <span className="detail-icon">📍</span>
                  <div className="detail-content">
                    <span className="detail-label">Emplacement</span>
                    <span className="detail-value">{item.location}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="item-actions">
              <button 
                className="action-btn primary"
                onClick={() => {
                  setSelectedItem(item);
                  setShowItemModal(true);
                }}
                title="Voir détails"
              >
                <span>👁️</span>
              </button>
              <button 
                className="action-btn secondary"
                onClick={() => handleEditItem(item)}
                title="Modifier"
              >
                <span>✏️</span>
              </button>
              <button 
                className="action-btn danger"
                onClick={() => handleDeleteItem(item.id)}
                title="Supprimer"
              >
                <span>🗑️</span>
              </button>
            </div>
          </div>
        ))}
      </div>

      {filteredItems.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">📦</div>
          <h3>Aucun article trouvé</h3>
          <p>Aucun article ne correspond à vos critères de recherche.</p>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Ajouter un article
          </button>
        </div>
      )}

      {/* Modal de création/modification */}
      {showCreateModal && (
        <div className="modal-overlay" onClick={() => setShowCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{editingItem ? 'Modifier l\'article' : 'Nouvel Article'}</h2>
              <button 
                className="modal-close"
                onClick={() => {
                  setShowCreateModal(false);
                  setEditingItem(null);
                  setNewItem({
                    name: '',
                    category: '',
                    quantity: 0,
                    location: '',
                    status: 'in_stock'
                  });
                }}
              >
                ✕
              </button>
            </div>
            
            <form className="item-form" onSubmit={(e) => {
              e.preventDefault();
              if (editingItem) {
                handleUpdateItem();
              } else {
                handleCreateItem();
              }
            }}>
              <div className="form-group">
                <label htmlFor="name">Nom de l'article *</label>
                <input
                  type="text"
                  id="name"
                  value={newItem.name || ''}
                  onChange={(e) => setNewItem(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="Ex: Ordinateurs portables"
                  required
                />
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="category">Catégorie *</label>
                  <select
                    id="category"
                    value={newItem.category || ''}
                    onChange={(e) => setNewItem(prev => ({ ...prev, category: e.target.value }))}
                    required
                  >
                    <option value="">Sélectionner une catégorie</option>
                    {categories.map(category => (
                      <option key={category} value={category}>{category}</option>
                    ))}
                  </select>
                </div>
                
                <div className="form-group">
                  <label htmlFor="quantity">Quantité *</label>
                  <input
                    type="number"
                    id="quantity"
                    value={newItem.quantity || 0}
                    onChange={(e) => setNewItem(prev => ({ ...prev, quantity: parseInt(e.target.value) || 0 }))}
                    min="0"
                    required
                  />
                </div>
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="location">Emplacement *</label>
                  <input
                    type="text"
                    id="location"
                    value={newItem.location || ''}
                    onChange={(e) => setNewItem(prev => ({ ...prev, location: e.target.value }))}
                    placeholder="Ex: Salle informatique A"
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="status">Statut *</label>
                  <select
                    id="status"
                    value={newItem.status || 'in_stock'}
                    onChange={(e) => setNewItem(prev => ({ ...prev, status: e.target.value as InventoryItem['status'] }))}
                    required
                  >
                    <option value="in_stock">En Stock</option>
                    <option value="in_use">En Utilisation</option>
                    <option value="under_maintenance">En Maintenance</option>
                  </select>
                </div>
              </div>
              
              <div className="form-actions">
                <button 
                  type="button" 
                  className="btn btn-outline"
                  onClick={() => {
                    setShowCreateModal(false);
                    setEditingItem(null);
                    setNewItem({
                      name: '',
                      category: '',
                      quantity: 0,
                      location: '',
                      status: 'in_stock'
                    });
                  }}
                >
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingItem ? 'Mettre à jour' : 'Ajouter l\'article'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de détails d'article */}
      {showItemModal && selectedItem && (
        <div className="modal-overlay" onClick={() => setShowItemModal(false)}>
          <div className="modal-content item-details-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{selectedItem.name}</h2>
              <button 
                className="modal-close"
                onClick={() => setShowItemModal(false)}
              >
                ✕
              </button>
            </div>
            
            <div className="item-details-content">
              <div className="item-meta">
                <span className="category-badge">{selectedItem.category}</span>
                <span className={`status-badge ${getStatusColor(selectedItem.status)}`}>
                  {getStatusLabel(selectedItem.status)}
                </span>
              </div>
              
              <div className="item-info">
                <div className="info-item">
                  <span className="info-icon">📦</span>
                  <div className="info-content">
                    <span className="info-label">Quantité</span>
                    <span className="info-value">{selectedItem.quantity} unités</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📍</span>
                  <div className="info-content">
                    <span className="info-label">Emplacement</span>
                    <span className="info-value">{selectedItem.location}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">🏷️</span>
                  <div className="info-content">
                    <span className="info-label">Catégorie</span>
                    <span className="info-value">{selectedItem.category}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📊</span>
                  <div className="info-content">
                    <span className="info-label">Statut</span>
                    <span className="info-value">{getStatusLabel(selectedItem.status)}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="modal-actions">
              <button 
                className="btn btn-outline"
                onClick={() => {
                  handleEditItem(selectedItem);
                  setShowItemModal(false);
                }}
              >
                <span>✏️</span>
                Modifier
              </button>
              <button 
                className="btn btn-danger"
                onClick={() => {
                  handleDeleteItem(selectedItem.id);
                  setShowItemModal(false);
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

(InventoryPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default InventoryPage;