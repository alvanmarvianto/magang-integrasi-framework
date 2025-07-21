import Swal from 'sweetalert2';

export function useNotification() {
  const showSuccess = (message: string) => {
    Swal.fire({
      title: 'Berhasil!',
      text: message,
      icon: 'success',
      confirmButtonText: 'OK',
      confirmButtonColor: 'var(--primary-color)',
    });
  };

  const showError = (message: string) => {
    Swal.fire({
      title: 'Error!',
      text: message,
      icon: 'error',
      confirmButtonText: 'OK',
      confirmButtonColor: 'var(--primary-color)',
    });
  };

  const showConfirm = async (message: string): Promise<boolean> => {
    const result = await Swal.fire({
      title: 'Konfirmasi',
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya',
      cancelButtonText: 'Batal',
      confirmButtonColor: 'var(--primary-color)',
      cancelButtonColor: 'var(--danger-color)',
    });

    return result.isConfirmed;
  };

  return {
    showSuccess,
    showError,
    showConfirm,
  };
} 