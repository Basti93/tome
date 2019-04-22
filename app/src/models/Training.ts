import TrainingParticipant from "@/models/TrainingParticipant";
import * as moment from "moment";

export default class Training {
    id: number;
    start: moment.Moment;
    end: moment.Moment;
    locationId: number;
    groupIds: number[];
    contentIds: number[];
    trainerIds: number[];
    participants: TrainingParticipant[];
    comment: string;

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        let participants = [] as TrainingParticipant[];
        for (let participant of jsonObj.trainingParticipants) {
            participants.push(TrainingParticipant.from(participant));
        }
        return new Training(jsonObj.id, jsonObj.start, jsonObj.end, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, jsonObj.trainerIds, participants, jsonObj.comment)
    }


    constructor(id: number, start: moment.Moment, end: moment.Moment, locationId: number, groupIds: number[], contentIds: number[], trainerIds: number[], participants: TrainingParticipant[], comment: string) {
        this.id = id;
        this.start = start;
        this.end = end;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.participants = participants;
        this.comment = comment;
    }
}
