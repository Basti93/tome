export default class SimpleUser {

  id: number;
  firstName: string;
  familyName: string;
  groupIds: number[];
  /** YYYY-MM-DD, only delivered to logged in trainers/admins */
  birthdate?: string | null;

  constructor (id: number, firstName: string, familyName: string, groupIds: number[], birthdate?: string | null) {
    this.birthdate = birthdate ?? null
    this.id = id
    // anonymous API responses abbreviate the family name ("M.") and may omit fields
    this.groupIds = groupIds ?? []
    this.firstName = firstName ?? ''
    this.familyName = familyName ?? ''

  }

  getFullName() {
    return this.firstName + " " + this.familyName;
  }

  getFullNameFamilyFirst() {
    return this.familyName + " " + this.firstName;
  }

}
