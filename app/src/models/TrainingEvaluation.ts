import TrainingParticipant from "./TrainingParticipant";
import TrainingTrainer from "./TrainingTrainer";
import * as moment from "moment";

export default class Training {
    id: number;
    start: moment.Moment;
    end: moment.Moment;
    locationId: number;
    groupIds: number[];
    contentIds: number[];
    trainers: TrainingTrainer[];
    participants: TrainingParticipant[];
    comment: string;
    evaluated: boolean;
    prepared: boolean;

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        let participants = [] as TrainingParticipant[];
        for (let participant of jsonObj.trainingParticipants) {
            participants.push(TrainingParticipant.from(participant));
        }
        let trainers = [] as TrainingTrainer[];
        for (let trainer of jsonObj.trainers) {
            trainers.push(TrainingTrainer.from(trainer));
        }
        return new Training(jsonObj.id, jsonObj.start, jsonObj.end, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, trainers, participants, jsonObj.comment, jsonObj.prepared === 1 ? true : false, jsonObj.evaluated === 1 ? true : false)
    }


    constructor(id: number, start: moment.Moment, end: moment.Moment, locationId: number, groupIds: number[], contentIds: number[], trainers: TrainingTrainer[], participants: TrainingParticipant[], comment: string, prepared: boolean, evaluated: boolean) {
        this.id = id;
        this.start = start;
        this.end = end;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainers = trainers;
        this.participants = participants;
        this.comment = comment;
        this.evaluated = evaluated;
        this.prepared = prepared;
    }
}
