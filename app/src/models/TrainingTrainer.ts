import type { Dayjs } from '@/helpers/dayjs'

export default class TrainingTrainer {

    trainingId: number;
    userId: number;
    accountingTimeStart: Dayjs;
    accountingTimeEnd: Dayjs;

    static from(jsonString: string) {
        const jsonObj = JSON.parse(jsonString);
        return new TrainingTrainer(jsonObj.trainingId, jsonObj.userId, jsonObj.accountingTimeStart, jsonObj.accountingTimeEnd)
    }

    constructor(cTrainingId: number, cUserId: number, accountingTimeStart: Dayjs, accountingTimeEnd: Dayjs) {
        this.trainingId = cTrainingId;
        this.userId = cUserId;
        this.accountingTimeStart = accountingTimeStart;
        this.accountingTimeEnd = accountingTimeEnd;
    }

}
