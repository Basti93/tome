import TrainingParticipant from "@/models/TrainingParticipant";
import * as moment from "moment";

export default class TrainingSeries {
    id: Number;
    startTime: String;
    endTime: String;
    locationId: Number;
    groupIds: Number[] = [];
    contentIds: Number[] = [];
    trainerIds: Number[] = [];
    comment: string;
    weekdays: Number[] = [];
    deferUntil: moment.Moment;
    automaticAttend: boolean;

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        return new TrainingSeries(jsonObj.id, jsonObj.startTime, jsonObj.endTime, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, jsonObj.trainerIds, jsonObj.comment, jsonObj.weekdays, jsonObj.deferUntil,  jsonObj.automaticAttend === 1 ? true : false)
    }

    constructor(id: Number, startTime: String, endTime: String, locationId: Number, groupIds: Number[], contentIds: Number[], trainerIds: Number[], comment: string, weekdays: Number[], deferUntil: moment.Moment, automaticAttend: boolean) {
        this.id = id;
        this.startTime = startTime;
        this.endTime = endTime;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.comment = comment;
        this.weekdays = weekdays;
        this.deferUntil = deferUntil;
        this.automaticAttend = automaticAttend;
    }
}
