import { parseApiDate, type Dayjs } from '@/helpers/dayjs'

export default class User {

    id: number;
    email: string;
    firstName: string;
    familyName: string;
    birthdate: Dayjs;
    active: boolean;
    groupIds: number[];
    roleNames: string [];
    trainerBranchIds: number[];
    registered: boolean;
    profileImageName: string;
    isAdmin: boolean;
    isTrainer: boolean;
    absenceStart: Dayjs;
    absenceEnd: Dayjs;
    absenceReason: string;
    notificationPreferences: {
        trainer_upcoming: boolean;
        trainer_cancel_alert: boolean;
        participant_upcoming: boolean;
        absence_alert: boolean;
        admin_messages: boolean;
        trainer_evaluation_reminder: boolean;
        trainer_evaluation_reminder_email: boolean;
        trainer_birthday: boolean;
    };

    static from(jsonString) {
        try {
            const jsonObj = JSON.parse(jsonString);
            const user = new User(jsonObj.id, jsonObj.email, jsonObj.firstName, jsonObj.familyName, jsonObj.birthdate, jsonObj.active === 1 ? true : false, jsonObj.groupIds, jsonObj.roleNames, jsonObj.trainerBranchIds, jsonObj.registered === 1 ? true : false, jsonObj.profileImageName, jsonObj.absenceStart, jsonObj.absenceEnd, jsonObj.absenceReason)
            user.notificationPreferences = jsonObj.notification_preferences || {
                trainer_upcoming: false,
                trainer_cancel_alert: true,
                participant_upcoming: true,
                absence_alert: true,
                admin_messages: true,
                trainer_evaluation_reminder: true,
                trainer_evaluation_reminder_email: false,
                trainer_birthday: true,
            }
            return user
        } catch (_) {
            return null
        }
    }


    constructor(id: number, email: string, firstName: string, familyName: string, birthdate: Dayjs, active: boolean, groupIds: number[], roleNames: string[], trainerBranchIds: number[], registered: boolean, profileImageName: string, absenceStart: Dayjs, absenceEnd: Dayjs, absenceReason: string) {
        this.id = id
        this.active = active
        this.groupIds = groupIds
        this.email = email  
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
        this.notificationPreferences = {
            trainer_upcoming: false,
            trainer_cancel_alert: true,
            participant_upcoming: true,
            absence_alert: true,
            admin_messages: true,
            trainer_evaluation_reminder: true,
            trainer_evaluation_reminder_email: false,
                trainer_birthday: true,
        }
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

/** A user as listed by the API (`/user`, `/user/allAbsence`); dates are parsed once, here (start and end separately). */
export function userFromApi(userObj: any): User {
  return new User(
    userObj.id,
    userObj.email,
    userObj.firstName,
    userObj.familyName,
    userObj.birthdate ? parseApiDate(userObj.birthdate) : null,
    userObj.active === 1,
    userObj.groupIds,
    userObj.roleNames,
    userObj.trainerBranchIds,
    userObj.registered,
    userObj.profileImageName,
    userObj.absenceStart ? parseApiDate(userObj.absenceStart) : null,
    userObj.absenceEnd ? parseApiDate(userObj.absenceEnd) : null,
    userObj.absenceReason,
  )
}
