export default class SimpleUser {

  id: number;
  firstName: string;
  familyName: string;
  groupIds: number[];

  constructor (id: number, firstName: string, familyName: string, groupIds: number[]) {
    this.id = id
    this.groupIds = groupIds
    this.firstName = firstName
    this.familyName = familyName
  }

  getFullName() {
    return this.firstName + " " + this.familyName;
  }

  getFullNameFamilyFirst() {
    return this.familyName + " " + this.firstName;
  }

}
