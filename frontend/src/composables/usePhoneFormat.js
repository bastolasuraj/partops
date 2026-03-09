/**
 * Phone number formatting utility
 */
export function usePhoneFormat() {
  /**
   * Format phone number with country code
   * @param {string} phone - Raw phone number (digits only or with formatting)
   * @returns {string} Formatted phone number
   */
  const formatPhone = (phone) => {
    if (!phone) return ''
    
    // Remove all non-digit characters
    const digits = phone.replace(/\D/g, '')
    
    if (digits.length === 0) return ''
    
    // If exactly 10 digits, assume US/Canada (+1)
    if (digits.length === 10) {
      return `+1 (${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
    }
    
    // If more than 10 digits, use everything before last 10 as country code
    if (digits.length > 10) {
      const countryCode = digits.slice(0, -10)
      const areaCode = digits.slice(-10, -7)
      const firstPart = digits.slice(-7, -4)
      const lastPart = digits.slice(-4)
      
      return `+${countryCode} (${areaCode}) ${firstPart}-${lastPart}`
    }
    
    // If less than 10 digits, return as-is with basic formatting
    if (digits.length === 7) {
      return `${digits.slice(0, 3)}-${digits.slice(3)}`
    }
    
    return digits
  }
  
  /**
   * Parse formatted phone back to digits only
   * @param {string} formattedPhone - Formatted phone number
   * @returns {string} Digits only
   */
  const parsePhone = (formattedPhone) => {
    if (!formattedPhone) return ''
    return formattedPhone.replace(/\D/g, '')
  }
  
  return {
    formatPhone,
    parsePhone
  }
}
