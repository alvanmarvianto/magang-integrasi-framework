export interface ContractPeriod {
  period_name: string;
  budget_type: string;
  start_date?: string;
  end_date?: string;
  payment_value_rp?: string;
  payment_value_non_rp?: string;
  payment_status: string;
  alert_status?: string; // Added for backend compatibility
  alert_message?: string; // Added for backend compatibility
}

export type AlertStatus = 'none' | 'warning' | 'danger';

/**
 * Determines the alert status for a contract period based on payment status and dates
 */
export function getContractPeriodAlertStatus(period: ContractPeriod): AlertStatus {
  // Use backend alert_status if available
  if (period.alert_status) {
    return period.alert_status as AlertStatus;
  }

  // Fallback to client-side calculation
  // Only check periods that are not paid and are actually due
  // Exclude: 'paid', 'not_due', 'reserved_hr', 'contract_moved'
  const safeStatuses = ['paid', 'not_due', 'reserved_hr', 'contract_moved'];
  if (safeStatuses.includes(period.payment_status)) {
    return 'none';
  }

  // Skip if no end date
  if (!period.end_date) {
    return 'none';
  }

  const now = new Date();
  const endDate = new Date(period.end_date);
  
  // Calculate the difference in days
  const diffTime = endDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  // If past end date (overdue)
  if (diffDays < 0) {
    return 'danger';
  }

  // If within 2 weeks (14 days) of end date
  if (diffDays <= 14) {
    return 'warning';
  }

  return 'none';
}

/**
 * Gets the highest alert status from an array of contract periods
 * Used for determining the overall contract alert status
 */
export function getContractAlertStatus(contractPeriods: ContractPeriod[]): AlertStatus {
  if (!contractPeriods || contractPeriods.length === 0) {
    return 'none';
  }

  let hasWarning = false;
  
  for (const period of contractPeriods) {
    const status = getContractPeriodAlertStatus(period);
    
    if (status === 'danger') {
      return 'danger'; // Danger takes precedence
    }
    
    if (status === 'warning') {
      hasWarning = true;
    }
  }
  
  return hasWarning ? 'warning' : 'none';
}

/**
 * Gets CSS classes for alert status
 */
export function getAlertClasses(alertStatus: AlertStatus): string[] {
  const classes = [];
  
  switch (alertStatus) {
    case 'warning':
      classes.push('alert-warning');
      break;
    case 'danger':
      classes.push('alert-danger');
      break;
    default:
      break;
  }
  
  return classes;
}

/**
 * Gets the alert message for a contract period
 */
export function getContractPeriodAlertMessage(period: ContractPeriod): string | null {
  // Use backend alert_message if available
  if (period.alert_message !== undefined) {
    return period.alert_message;
  }

  // Fallback to client-side calculation
  const alertStatus = getContractPeriodAlertStatus(period);
  
  if (alertStatus === 'none') {
    return null;
  }

  if (!period.end_date) {
    return null;
  }

  const now = new Date();
  const endDate = new Date(period.end_date);
  const diffTime = endDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (alertStatus === 'danger') {
    const overdueDays = Math.abs(diffDays);
    return `Pembayaran terlambat ${overdueDays} hari`;
  }

  if (alertStatus === 'warning') {
    return `Pembayaran jatuh tempo dalam ${diffDays} hari`;
  }

  return null;
}
