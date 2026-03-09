// Types pour smartSchool RDC

export type Currency = 'CDF' | 'USD';

export type UserRole = 'admin' | 'teacher' | 'parent' | 'student';

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  role: UserRole;
  phone?: string;
  avatar?: string;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface Student {
  id: string;
  matricule: string;
  studentNumber: string;
  firstName: string;
  lastName: string;
  dateOfBirth: Date;
  gender: 'M' | 'F';
  placeOfBirth?: string;
  nationality?: string;
  bloodGroup?: string;
  photo?: string;
  
  // Contact
  address?: string;
  city?: string;
  province?: string;
  phone?: string;
  email?: string;
  
  // Guardian/Parent
  parentIds: string[];
  guardianName: string;
  guardianRelation?: string;
  guardianPhone: string;
  guardianEmail?: string;
  
  // Academic
  classId: string;
  class: string; // Display name like "6ème A"
  academicYear?: string;
  academicStatus?: string;
  previousSchool?: string;
  enrollmentDate: Date;
  
  // Health & Medical
  allergies?: string;
  medicalConditions?: string;
  emergencyContact?: string;
  medicalInfo?: {
    allergies?: string[];
    medications?: string[];
    emergencyContact?: string;
  };
  
  // Status
  isActive: boolean;
  status: 'active' | 'inactive' | 'suspended';
}

export interface Class {
  id: string;
  name: string;
  level: string;
  capacity: number;
  currentStudents: number;
  teacherId: string;
  schedule: {
    day: string;
    startTime: string;
    endTime: string;
  }[];
}

export interface Payment {
  id: string;
  studentId: string;
  amount: number;
  currency: Currency;
  type: 'tuition' | 'registration' | 'exam' | 'uniform' | 'transport' | 'meal' | 'other';
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  paymentMethod: 'mobile_money' | 'cash' | 'bank_transfer';
  method?: 'mobile_money' | 'cash' | 'bank_transfer'; // Alias for compatibility
  mobileMoneyProvider?: 'mpesa' | 'orange_money' | 'airtel_money';
  transactionId?: string;
  reference?: string;
  description?: string;
  qrCode?: string;
  dueDate: Date;
  paidAt?: Date;
  createdAt: Date;
  student?: any;
}

export interface Application {
  id: string;
  studentInfo: {
    firstName: string;
    lastName: string;
    dateOfBirth: Date;
    gender: 'M' | 'F';
  };
  parentInfo: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
  };
  documents: {
    type: string;
    url: string;
    uploadedAt: Date;
  }[];
  status: 'submitted' | 'under_review' | 'accepted' | 'rejected';
  appliedClass: string;
  submittedAt: Date;
  reviewedAt?: Date;
  reviewedBy?: string;
  notes?: string;
}

export interface Notification {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'success' | 'error';
  recipients: string[];
  sentAt: Date;
  readBy: string[];
  channels: ('push' | 'sms' | 'email')[];
}

export interface DashboardStats {
  totalStudents: number;
  totalTeachers: number;
  totalParents: number;
  pendingApplications: number;
  totalRevenue: {
    cdf: number;
    usd: number;
  };
  pendingPayments: {
    cdf: number;
    usd: number;
  };
  recentActivities: {
    type: string;
    description: string;
    timestamp: Date;
  }[];
}
export interface SchoolEvent {
  id: string;
  title: string;
  description: string;
  date: Date;
  location: string;
  organizer: string;
  media?: {
    type: 'image' | 'video' | 'document';
    url: string;
    name: string;
    size?: number;
  }[];
}

export interface InventoryItem {
  id: string;
  name: string;
  category: string;
  quantity: number;
  location: string;
  status: 'in_stock' | 'in_use' | 'under_maintenance';
}