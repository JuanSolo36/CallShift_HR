export interface CompanyItem {
  id: number;
  name: string;
  legal_name: string;
  tax_id: string;
  slug?: string | null;
  email: string;
  phone?: string | null;
  address?: string | null;
  city?: string | null;
  country: string;
  timezone: string;
  currency: string;
  date_format: string;
  logo?: string | null;
  primary_color: string;
  secondary_color: string;
  status: 'ACTIVE' | 'INACTIVE';
  created_at?: string;
  updated_at?: string;
}

export interface UpdateCompanyPayload {
  name: string;
  legal_name: string;
  tax_id: string;
  slug?: string | null;
  email: string;
  phone?: string | null;
  address?: string | null;
  city?: string | null;
  country: string;
  timezone: string;
  currency: string;
  date_format: string;
  logo?: string | null;
  primary_color?: string;
  secondary_color?: string;
}

export interface UpdateCompanySettingsPayload {
  timezone?: string;
  currency?: string;
  date_format?: string;
  primary_color?: string;
  secondary_color?: string;
  settings?: Record<string, unknown>;
}
