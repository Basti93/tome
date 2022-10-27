import * as moment from "moment";

export default class TrainingTrainer {

    trainingId: number;
    userId: number;
    accountingTimeStart: moment.Moment;
    accountingTimeEnd: moment.Moment;

    static from(jsonString: string) {
        const jsonObj = JSON.parse(jsonString);
        return new TrainingTrainer(jsonObj.trainingId, jsonObj.userId, jsonObj.accountingTimeStart, jsonObj.accountingTimeEnd)
    }

    constructor(cTrainingId: number, cUserId: number, accountingTimeStart: moment.Moment, accountingTimeEnd: moment.Moment) {
        this.trainingId = cTrainingId;
        this.userId = cUserId;
        this.accountingTimeStart = accountingTimeStart;
        this.accountingTimeEnd = accountingTimeEnd;
    }

}
