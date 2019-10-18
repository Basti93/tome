import * as moment from "moment";

export default class TrainingCalendarEntry {
    id: number;
    start: String;
    end: String;
    locationId: number;
    groupIds: number[];
    contentIds: number[];
    trainerIds: number[];
    comment: string;

    constructor(id: number, start: String, end: String, locationId: number, groupIds: number[], contentIds: number[], trainerIds: number[], comment: string) {
        this.id = id;
        this.start = start;
        this.end = end;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.comment = comment;
    }
}
