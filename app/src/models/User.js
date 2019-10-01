
export default class User {
  static from (jsonString) {
    try {
      let jsonObj = JSON.parse(jsonString);
      return new User({id: jsonObj.id, email: jsonObj.email, firstName: jsonObj.firstName, familyName: jsonObj.familyName, birthdate: jsonObj.birthdate, active: jsonObj.active, groupIds: jsonObj.groupIds, roleNames: jsonObj.roleNames, trainerGroupIds: jsonObj.trainerGroupIds, registered: jsonObj.registered, profileImageName: jsonObj.profileImageName})
    } catch (_) {
      return null
    }
  }


  constructor ({ id, email, firstName, familyName, birthdate, active, groupIds, roleNames, trainerGroupIds, registered, profileImageName }) {
    this.id = id
    this.active = active == 1 ? true : false
    this.groupIds = groupIds
    this.email = email // eslint-disable-line camelcase
    this.firstName = firstName
    this.familyName = familyName
    this.birthdate = birthdate
    this.trainerGroupIds = trainerGroupIds
    this.roleNames = roleNames
    this.profileImageName = profileImageName
    this.registered = registered == 1 ? true : false
    if (this.roleNames) {
      this.isAdmin = this.roleNames.includes('admin')
      this.isTrainer = this.roleNames.includes('trainer')
    }
  }

  getFullName = function () {
    return this.firstName + " " + this.familyName;
  }

}
