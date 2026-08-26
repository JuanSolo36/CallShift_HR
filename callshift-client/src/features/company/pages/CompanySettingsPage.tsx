import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/Card';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Tabs } from '@/components/navigation/Tabs';
import { LoadingState } from '@/components/feedback/LoadingState';
import { useCompany } from '../hooks/useCompany';
import { useAuthStore } from '@/stores/useAuthStore';
import { Building2, Globe, Palette, Save, ShieldCheck, CheckCircle2 } from 'lucide-react';
import type { UpdateCompanyPayload } from '@/types/company.types';

const companyFormSchema = z.object({
  name: z.string().min(2, 'El nombre comercial es obligatorio.'),
  legal_name: z.string().min(2, 'La razón social es obligatoria.'),
  tax_id: z.string().min(3, 'El número de identificación tributaria (NIT) es obligatorio.'),
  slug: z.string().optional().nullable(),
  email: z.string().email('Debe ingresar un correo electrónico válido.'),
  phone: z.string().optional().nullable(),
  address: z.string().optional().nullable(),
  city: z.string().optional().nullable(),
  country: z.string().length(3, 'El código de país debe ser ISO Alpha-3 (ej. COL).'),
  timezone: z.string().min(2, 'La zona horaria es obligatoria.'),
  currency: z.string().min(2, 'La moneda es obligatoria.'),
  date_format: z.string().min(2, 'El formato de fecha es obligatorio.'),
  logo: z.string().optional().nullable(),
  primary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Formato hexadecimal inválido (ej. #0284c7).'),
  secondary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Formato hexadecimal inválido (ej. #0f172a).'),
});

type CompanyFormData = z.infer<typeof companyFormSchema>;

export const CompanySettingsPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const { company, isLoading, updateCompany, isUpdatingCompany } = useCompany();
  const [activeTab, setActiveTab] = useState('general');

  const canEdit = hasPermission('company:update') || hasPermission('settings:manage');

  const {
    register,
    handleSubmit,
    reset,
    watch,
    formState: { errors, isDirty },
  } = useForm<CompanyFormData>({
    resolver: zodResolver(companyFormSchema),
    defaultValues: {
      name: '',
      legal_name: '',
      tax_id: '',
      slug: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      country: 'COL',
      timezone: 'America/Bogota',
      currency: 'COP',
      date_format: 'YYYY-MM-DD',
      logo: '',
      primary_color: '#0284c7',
      secondary_color: '#0f172a',
    },
  });

  useEffect(() => {
    if (company) {
      reset({
        name: company.name || '',
        legal_name: company.legal_name || '',
        tax_id: company.tax_id || '',
        slug: company.slug || '',
        email: company.email || '',
        phone: company.phone || '',
        address: company.address || '',
        city: company.city || '',
        country: company.country || 'COL',
        timezone: company.timezone || 'America/Bogota',
        currency: company.currency || 'COP',
        date_format: company.date_format || 'YYYY-MM-DD',
        logo: company.logo || '',
        primary_color: company.primary_color || '#0284c7',
        secondary_color: company.secondary_color || '#0f172a',
      });
    }
  }, [company, reset]);

  const watchedPrimary = watch('primary_color') || '#0284c7';
  const watchedSecondary = watch('secondary_color') || '#0f172a';
  const watchedName = watch('name') || 'Mi Empresa';

  const onSubmit = (data: CompanyFormData) => {
    updateCompany(data as UpdateCompanyPayload);
  };

  const tabs = [
    { id: 'general', label: 'Información General', icon: <Building2 className="w-4 h-4" /> },
    { id: 'regional', label: 'Configuración Regional', icon: <Globe className="w-4 h-4" /> },
    { id: 'branding', label: 'Identidad Visual', icon: <Palette className="w-4 h-4" /> },
  ];

  const timezoneOptions = [
    { value: 'America/Bogota', label: 'America/Bogota (UTC-5 - Colombia, Perú, Ecuador)' },
    { value: 'America/Mexico_City', label: 'America/Mexico_City (UTC-6 - México)' },
    { value: 'America/Santiago', label: 'America/Santiago (UTC-3 / UTC-4 - Chile)' },
    { value: 'America/Argentina/Buenos_Aires', label: 'America/Argentina/Buenos_Aires (UTC-3 - Argentina)' },
    { value: 'America/Lima', label: 'America/Lima (UTC-5 - Perú)' },
    { value: 'America/Caracas', label: 'America/Caracas (UTC-4 - Venezuela)' },
    { value: 'America/New_York', label: 'America/New_York (UTC-5 / UTC-4 - Este EE.UU.)' },
    { value: 'UTC', label: 'UTC (Tiempo Universal Coordinado)' },
  ];

  const currencyOptions = [
    { value: 'COP', label: 'COP - Peso Colombiano ($)' },
    { value: 'USD', label: 'USD - Dólar Estadounidense ($)' },
    { value: 'EUR', label: 'EUR - Euro (€)' },
    { value: 'MXN', label: 'MXN - Peso Mexicano ($)' },
    { value: 'CLP', label: 'CLP - Peso Chileno ($)' },
    { value: 'PEN', label: 'PEN - Sol Peruano (S/)' },
  ];

  const dateFormatOptions = [
    { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD (ISO Estándar - 2026-08-21)' },
    { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY (Latinoamérica - 21/08/2026)' },
    { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY (Norteamérica - 08/21/2026)' },
  ];

  if (isLoading) {
    return <LoadingState message="Cargando configuración de la empresa..." />;
  }

  return (
    <div className="space-y-6 text-left select-none">
      <PageHeader
        title="Configuración de la Empresa"
        description="Parámetros corporativos, ajustes regionales y personalización del tenant."
        breadcrumbs={[
          { label: 'Administración' },
          { label: 'Configuración de Empresa', current: true },
        ]}
        actions={
          company && (
            <Badge variant="success" size="sm" dot>
              Tenant Activo (ID: {company.id})
            </Badge>
          )
        }
      />

      <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

      <form onSubmit={handleSubmit(onSubmit)}>
        {/* Tab 1: General */}
        {activeTab === 'general' && (
          <Card>
            <CardHeader>
              <CardTitle>Datos Corporativos</CardTitle>
              <CardDescription>
                Información legal y de contacto principal de la organización.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  label="Nombre Comercial"
                  required
                  placeholder="CallShift Enterprise"
                  error={errors.name?.message}
                  disabled={!canEdit}
                  {...register('name')}
                />

                <Input
                  label="Razón Social"
                  required
                  placeholder="CallShift Technologies S.A.S."
                  error={errors.legal_name?.message}
                  disabled={!canEdit}
                  {...register('legal_name')}
                />

                <Input
                  label="NIT / Identificación Tributaria"
                  required
                  placeholder="901.845.120-4"
                  error={errors.tax_id?.message}
                  disabled={!canEdit}
                  {...register('tax_id')}
                />

                <Input
                  label="Slug / Identificador de Enlace"
                  placeholder="callshift-enterprise"
                  helperText="Identificador único para enlaces corporativos."
                  error={errors.slug?.message}
                  disabled={!canEdit}
                  {...register('slug')}
                />

                <Input
                  label="Correo Electrónico de Contacto"
                  type="email"
                  required
                  placeholder="contacto@empresa.com"
                  error={errors.email?.message}
                  disabled={!canEdit}
                  {...register('email')}
                />

                <Input
                  label="Teléfono Principal"
                  placeholder="+57 (601) 745-9000"
                  error={errors.phone?.message}
                  disabled={!canEdit}
                  {...register('phone')}
                />

                <Input
                  label="Dirección Física"
                  placeholder="Av. El Dorado #68C-61, Piso 10"
                  error={errors.address?.message}
                  disabled={!canEdit}
                  {...register('address')}
                />

                <div className="grid grid-cols-2 gap-3">
                  <Input
                    label="Ciudad"
                    placeholder="Bogotá"
                    error={errors.city?.message}
                    disabled={!canEdit}
                    {...register('city')}
                  />
                  <Input
                    label="País (ISO Alpha-3)"
                    required
                    placeholder="COL"
                    maxLength={3}
                    error={errors.country?.message}
                    disabled={!canEdit}
                    {...register('country')}
                  />
                </div>
              </div>
            </CardContent>
            {canEdit && (
              <CardFooter className="flex justify-between items-center bg-surface-50 border-t border-surface-100 p-4">
                <div className="flex items-center gap-1.5 text-xs text-surface-500">
                  <ShieldCheck className="w-4 h-4 text-brand-600" />
                  <span>Los cambios son registrados con trazabilidad forense.</span>
                </div>
                <Button
                  type="submit"
                  variant="primary"
                  size="sm"
                  leftIcon={<Save className="w-4 h-4" />}
                  isLoading={isUpdatingCompany}
                  disabled={!isDirty}
                >
                  Guardar Información
                </Button>
              </CardFooter>
            )}
          </Card>
        )}

        {/* Tab 2: Regional */}
        {activeTab === 'regional' && (
          <Card>
            <CardHeader>
              <CardTitle>Configuración Regional y Horaria</CardTitle>
              <CardDescription>
                Ajustes de zona horaria para la programación de turnos y visualización de monedas.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Select
                  label="Zona Horaria Predeterminada"
                  required
                  options={timezoneOptions}
                  error={errors.timezone?.message}
                  disabled={!canEdit}
                  {...register('timezone')}
                />

                <Select
                  label="Moneda del Sistema"
                  required
                  options={currencyOptions}
                  error={errors.currency?.message}
                  disabled={!canEdit}
                  {...register('currency')}
                />

                <Select
                  label="Formato de Fechas"
                  required
                  options={dateFormatOptions}
                  error={errors.date_format?.message}
                  disabled={!canEdit}
                  {...register('date_format')}
                />
              </div>
            </CardContent>
            {canEdit && (
              <CardFooter className="flex justify-end bg-surface-50 border-t border-surface-100 p-4">
                <Button
                  type="submit"
                  variant="primary"
                  size="sm"
                  leftIcon={<Save className="w-4 h-4" />}
                  isLoading={isUpdatingCompany}
                  disabled={!isDirty}
                >
                  Guardar Configuración Regional
                </Button>
              </CardFooter>
            )}
          </Card>
        )}

        {/* Tab 3: Branding */}
        {activeTab === 'branding' && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card className="lg:col-span-2">
              <CardHeader>
                <CardTitle>Personalización Visual</CardTitle>
                <CardDescription>
                  Colores corporativos y logotipo para personalizar la experiencia del tenant.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <Input
                  label="URL del Logotipo Corporativo"
                  placeholder="https://empresa.com/assets/logo.png"
                  helperText="Enlace a imagen PNG o SVG transparente."
                  error={errors.logo?.message}
                  disabled={!canEdit}
                  {...register('logo')}
                />

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <Input
                      label="Color Primario (Hex)"
                      placeholder="#0284c7"
                      error={errors.primary_color?.message}
                      disabled={!canEdit}
                      {...register('primary_color')}
                    />
                    <div className="flex items-center gap-2 mt-2">
                      <div
                        className="w-8 h-8 rounded border border-surface-200 shadow-sm"
                        style={{ backgroundColor: watchedPrimary }}
                      />
                      <span className="text-xs font-mono text-surface-600">{watchedPrimary}</span>
                    </div>
                  </div>

                  <div>
                    <Input
                      label="Color Secundario (Hex)"
                      placeholder="#0f172a"
                      error={errors.secondary_color?.message}
                      disabled={!canEdit}
                      {...register('secondary_color')}
                    />
                    <div className="flex items-center gap-2 mt-2">
                      <div
                        className="w-8 h-8 rounded border border-surface-200 shadow-sm"
                        style={{ backgroundColor: watchedSecondary }}
                      />
                      <span className="text-xs font-mono text-surface-600">{watchedSecondary}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
              {canEdit && (
                <CardFooter className="flex justify-end bg-surface-50 border-t border-surface-100 p-4">
                  <Button
                    type="submit"
                    variant="primary"
                    size="sm"
                    leftIcon={<Save className="w-4 h-4" />}
                    isLoading={isUpdatingCompany}
                    disabled={!isDirty}
                  >
                    Guardar Identidad Visual
                  </Button>
                </CardFooter>
              )}
            </Card>

            {/* Live Preview Card */}
            <Card>
              <CardHeader>
                <CardTitle className="text-sm">Previsualización de Marca</CardTitle>
                <CardDescription className="text-xs">
                  Muestra interactiva de la identidad visual del tenant.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div
                  className="p-4 rounded-xl text-white shadow-md transition-colors"
                  style={{ backgroundColor: watchedPrimary }}
                >
                  <div className="text-xs font-medium opacity-80 uppercase tracking-wider">Tenant Activo</div>
                  <div className="text-lg font-bold truncate">{watchedName}</div>
                </div>

                <div
                  className="p-3 rounded-lg border text-xs flex items-center justify-between"
                  style={{ borderColor: watchedSecondary, color: watchedSecondary }}
                >
                  <span className="font-medium">Botón Secundario de Muestra</span>
                  <CheckCircle2 className="w-4 h-4" />
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </form>
    </div>
  );
};
