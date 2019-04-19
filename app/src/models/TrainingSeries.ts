import TrainingParticipant from "@/models/TrainingParticipant";

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

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        return new TrainingSeries(jsonObj.id, jsonObj.startTime, jsonObj.endTime, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, jsonObj.trainerIds, jsonObj.comment, jsonObj.weekdays)
    }

    constructor(id: Number, startTime: String, endTime: String, locationId: Number, groupIds: Number[], contentIds: Number[], trainerIds: Number[], comment: string, weekdays: Number[]) {
        this.id = id;
        this.startTime = startTime;
        this.endTime = endTime;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.comment = comment;
        this.weekdays = weekdays;
    }
}
