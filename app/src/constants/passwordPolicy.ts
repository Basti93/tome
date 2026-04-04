/**
 * Password Policy Configuration
 * IMPORTANT: Keep this in sync with server/config/boilerplate.php password_policy settings
 */

export const PASSWORD_POLICY = {
  min_length: 8,
  require_lowercase: true,
  require_uppercase: true,
  require_digits: true,
  require_special: true,
  special_chars: '@$!%*?&.',
}

export const getPasswordRequirements = (): string[] => {
  const requirements = []
  if (PASSWORD_POLICY.require_lowercase) requirements.push('Kleinbuchstaben')
  if (PASSWORD_POLICY.require_uppercase) requirements.push('Großbuchstaben')
  if (PASSWORD_POLICY.require_digits) requirements.push('Zahlen')
  if (PASSWORD_POLICY.require_special) requirements.push(`Sonderzeichen (${PASSWORD_POLICY.special_chars})`)
  return requirements
}

export const getPasswordHint = (): string => {
  const requirements = getPasswordRequirements()
  return `Min. ${PASSWORD_POLICY.min_length} Zeichen: ${requirements.join(', ')}`
}

export const getPasswordValidationRules = () => [
  (v: string) => !!v || 'Passwort wird benötigt',
  (v: string) => (v && v.length >= PASSWORD_POLICY.min_length) || `Mindestens ${PASSWORD_POLICY.min_length} Zeichen erforderlich`,
  (v: string) => !PASSWORD_POLICY.require_lowercase || (v && /[a-z]/.test(v)) || 'Mindestens ein Kleinbuchstabe erforderlich',
  (v: string) => !PASSWORD_POLICY.require_uppercase || (v && /[A-Z]/.test(v)) || 'Mindestens ein Großbuchstabe erforderlich',
  (v: string) => !PASSWORD_POLICY.require_digits || (v && /[0-9]/.test(v)) || 'Mindestens eine Ziffer erforderlich',
  (v: string) => !PASSWORD_POLICY.require_special || (v && new RegExp(`[${PASSWORD_POLICY.special_chars.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}]`).test(v)) || `Mindestens ein Sonderzeichen (${PASSWORD_POLICY.special_chars}) erforderlich`,
]
