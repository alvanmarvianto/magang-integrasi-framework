// Contract utilities
export function getAppDisplayText(contract: any, app: any): string {
  if (contract?.apps && contract.apps.length > 0) {
    return contract.apps.map((contractApp: any) => contractApp.app_name).join(', ');
  } else if (app) {
    return app.app_name;
  }
  return 'Tidak ada aplikasi terkait';
}

export function getFinancialFields(contract: any): Array<{label: string, value: string | number, isRupiah: boolean}> {
  const fields = [];
  
  if (contract?.currency_type === 'rp') {
    if (contract.contract_value_rp) {
      fields.push({
        label: 'Nilai Kontrak (RP)',
        value: contract.contract_value_rp,
        isRupiah: true
      });
    }
    if (contract.lumpsum_value_rp) {
      fields.push({
        label: 'Nilai Lumpsum (RP)',
        value: contract.lumpsum_value_rp,
        isRupiah: true
      });
    }
    if (contract.unit_value_rp) {
      fields.push({
        label: 'Nilai Satuan (RP)',
        value: contract.unit_value_rp,
        isRupiah: true
      });
    }
  } else if (contract?.currency_type === 'non_rp') {
    if (contract.contract_value_non_rp) {
      fields.push({
        label: 'Nilai Kontrak (Non-RP)',
        value: contract.contract_value_non_rp,
        isRupiah: false
      });
    }
  }
  
  return fields;
}

export function formatDate(dateString: string): string {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

export function formatCurrency(value: string | number, isRupiah: boolean = true): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(numValue)) return 'N/A';
  
  if (isRupiah) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numValue);
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(numValue);
}
