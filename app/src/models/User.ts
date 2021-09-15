import * as moment from "moment";

export default class User {

  id: number;
  email: string;
  firstName: string;
  familyName: string;
  birthdate: moment.Moment;
  active: boolean;
  groupIds: number[];
  roleNames: string [];
  trainerBranchIds: number[];
  registered: boolean;
  profileImageName: string;
  isAdmin: boolean;
  isTrainer: boolean;

  static from (jsonString) {
    try {
      let jsonObj = JSON.parse(jsonString);
      return new User({id: jsonObj.id, email: jsonObj.email, firstName: jsonObj.firstName, familyName: jsonObj.familyName, birthdate: jsonObj.birthdate, active: jsonObj.active, groupIds: jsonObj.groupIds, roleNames: jsonObj.roleNames, trainerBranchIds: jsonObj.trainerBranchIds, registered: jsonObj.registered, profileImageName: jsonObj.profileImageName})
    } catch (_) {
      return null
    }
  }


  constructor ({ id, email, firstName, familyName, birthdate, active, groupIds, roleNames, trainerBranchIds, registered, profileImageName }) {
    this.id = id
    this.active = active == 1 ? true : false
    this.groupIds = groupIds
    this.email = email // eslint-disable-line camelcase
    this.firstName = firstName
    this.familyName = familyName
    this.birthdate = birthdate
    this.trainerBranchIds = trainerBranchIds
    this.roleNames = roleNames
    this.profileImageName = profileImageName
    this.registered = registered == 1 ? true : false
    if (this.roleNames) {
      this.isAdmin = this.roleNames.includes('admin')
      this.isTrainer = this.roleNames.includes('trainer')
    }
  }

  getFullName() {
    return this.firstName + " " + this.familyName;
  }

  getFullNameFamilyFirst() {
    return this.familyName + " " + this.firstName;
  }

}
