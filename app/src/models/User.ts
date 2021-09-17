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
    absenceStart: moment.Moment;
    absenceEnd: moment.Moment;
    absenceReason: string;

    static from(jsonString) {
        try {
            let jsonObj = JSON.parse(jsonString);
            return new User(jsonObj.id, jsonObj.email, jsonObj.firstName, jsonObj.familyName, jsonObj.birthdate, jsonObj.active === 1 ? true : false, jsonObj.groupIds, jsonObj.roleNames, jsonObj.trainerBranchIds, jsonObj.registered === 1 ? true : false, jsonObj.profileImageName, jsonObj.absenceStart, jsonObj.absenceEnd, jsonObj.absenceReason)
        } catch (_) {
            return null
        }
    }


    constructor(id: number, email: string, firstName: string, familyName: string, birthdate: moment.Moment, active: boolean, groupIds: number[], roleNames: string[], trainerBranchIds: number[], registered: boolean, profileImageName: string, absenceStart: moment.Moment, absenceEnd: moment.Moment, absenceReason: string) {
        this.id = id
        this.active = active
        this.groupIds = groupIds
        this.email = email // eslint-disable-line camelcase
        this.firstName = firstName
        this.familyName = familyName
        this.birthdate = birthdate
        this.trainerBranchIds = trainerBranchIds
        this.roleNames = roleNames
        this.profileImageName = profileImageName
        this.registered = registered
        this.absenceStart = absenceStart
        this.absenceEnd = absenceEnd
        this.absenceReason = absenceReason
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
