
export default class User {
  static from (jsonString) {
    try {
      let jsonObj = JSON.parse(jsonString);
      return new User({id: jsonObj.id, email: jsonObj.email, firstName: jsonObj.firstName, familyName: jsonObj.familyName, active: jsonObj.active, groupId: jsonObj.groupId, roleNames: jsonObj.roleNames, trainerGroupIds: jsonObj.trainerGroupIds, registered: jsonObj.registered})
    } catch (_) {
      return null
    }
  }


  constructor ({ id, email, firstName, familyName, active, groupId, roleNames, trainerGroupIds, registered }) {
    this.id = id
    this.active = active == 1 ? true : false
    this.groupId = groupId
    this.email = email // eslint-disable-line camelcase
    this.firstName = firstName
    this.familyName = familyName
    this.trainerGroupIds = trainerGroupIds
    this.roleNames = roleNames
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
