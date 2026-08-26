import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAuth } from '../hooks/useAuth';
import { Input } from '@/components/forms/Input';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/feedback/Alert';
import { ToastContainer } from '@/components/feedback/Toast';
import { Lock, Mail, Eye, EyeOff, ShieldCheck } from 'lucide-react';
import type { AxiosError } from 'axios';
import type { ApiResponse } from '@/types/api.types';

const loginSchema = z.object({
  login: z.string().min(1, 'Ingrese su correo electrónico o usuario corporativo.'),
  password: z.string().min(1, 'Ingrese su contraseña.'),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export const LoginPage: React.FC = () => {
  const { login, isLoggingIn } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      login: '',
      password: '',
    },
  });

  const onSubmit = (data: LoginFormValues) => {
    setErrorMessage(null);
    login(
      {
        login: data.login,
        password: data.password,
        device_name: 'Web Browser',
      },
      {
        onError: (err: unknown) => {
          const axiosError = err as AxiosError<ApiResponse>;
          const msg = axiosError.response?.data?.message || 'Credenciales inválidas. Por favor verifique sus datos.';
          setErrorMessage(msg);
        },
      }
    );
  };

  const handleFillDemo = (userLogin: string, pass: string) => {
    setValue('login', userLogin);
    setValue('password', pass);
  };

  return (
    <div className="min-h-screen bg-surface-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 antialiased select-none">
      <ToastContainer />

      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        {/* Brand Logo */}
        <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-brand-600 text-white font-bold text-lg tracking-tight shadow-md mb-4">
          CS
        </div>
        <h1 className="text-2xl font-bold tracking-tight text-surface-900">CallShift HR</h1>
        <p className="text-xs text-surface-500 mt-1 max-w-xs mx-auto">
          Gestión Integral de Recursos Humanos y Planificación de Jornadas
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-10 rounded-2xl shadow-sm border border-surface-200">
          {errorMessage && (
            <div className="mb-6">
              <Alert variant="error" onClose={() => setErrorMessage(null)}>
                {errorMessage}
              </Alert>
            </div>
          )}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <Input
              label="Usuario o Correo Electrónico"
              placeholder="ejemplo@callshift.com"
              leftIcon={<Mail className="w-4 h-4" />}
              error={errors.login?.message}
              {...register('login')}
            />

            <div className="relative">
              <Input
                label="Contraseña"
                type={showPassword ? 'text' : 'password'}
                placeholder="••••••••••••"
                leftIcon={<Lock className="w-4 h-4" />}
                rightIcon={
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="text-surface-400 hover:text-surface-600 p-1"
                    aria-label={showPassword ? 'Ocultar contraseña' : 'Ver contraseña'}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                }
                error={errors.password?.message}
                {...register('password')}
              />
            </div>

            <div className="pt-2">
              <Button
                type="submit"
                variant="primary"
                size="md"
                className="w-full"
                isLoading={isLoggingIn}
              >
                Iniciar Sesión
              </Button>
            </div>
          </form>

          {/* Demo Access Helper for Quick Evaluation */}
          <div className="mt-8 pt-6 border-t border-surface-100">
            <div className="flex items-center gap-2 mb-3 text-xs font-semibold uppercase tracking-wider text-surface-400">
              <ShieldCheck className="w-4 h-4 text-brand-500" />
              <span>Accesos de Demostración</span>
            </div>

            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => handleFillDemo('admin@callshift.com', 'Admin123*')}
                className="p-2 text-left rounded-lg bg-surface-50 hover:bg-surface-100 border border-surface-200 text-xs transition-colors"
              >
                <div className="font-semibold text-surface-900">Super Admin</div>
                <div className="text-[11px] text-surface-400">admin@callshift.com</div>
              </button>

              <button
                type="button"
                onClick={() => handleFillDemo('carlos.mendoza@callshift.com', 'Password123*')}
                className="p-2 text-left rounded-lg bg-surface-50 hover:bg-surface-100 border border-surface-200 text-xs transition-colors"
              >
                <div className="font-semibold text-surface-900">RRHH Admin</div>
                <div className="text-[11px] text-surface-400">carlos.mendoza...</div>
              </button>
            </div>
          </div>
        </div>

        <p className="text-center text-xs text-surface-400 mt-6">
          CallShift HR &bull; Plataforma Empresarial Segura v1.0.0
        </p>
      </div>
    </div>
  );
};
