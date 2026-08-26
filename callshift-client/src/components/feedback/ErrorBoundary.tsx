import { Component, type ErrorInfo, type ReactNode } from 'react';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '../ui/Button';

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Uncaught error caught by ErrorBoundary:', error, errorInfo);
  }

  public handleReset = () => {
    this.setState({ hasError: false, error: null });
    window.location.reload();
  };

  public render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen flex items-center justify-center p-6 bg-surface-50 text-center">
          <div className="max-w-md w-full bg-white rounded-xl shadow-sm border border-surface-200 p-8 space-y-4">
            <div className="w-12 h-12 rounded-full bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto">
              <AlertCircle className="w-6 h-6" />
            </div>
            <h2 className="text-base font-bold text-surface-900">Ha ocurrido un error inesperado</h2>
            <p className="text-xs text-surface-500 leading-relaxed">
              La aplicación encontró un problema de renderizado. Puede intentar recargar la página.
            </p>
            <div className="pt-2">
              <Button size="sm" variant="primary" leftIcon={<RefreshCw className="w-3.5 h-3.5" />} onClick={this.handleReset}>
                Recargar aplicación
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
