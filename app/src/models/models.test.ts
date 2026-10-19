import { describe, expect, it } from 'vitest'
import Branch from '@/models/Branch'
import Group from '@/models/Group'
import SimpleUser from '@/models/SimpleUser'
import Training from '@/models/Training'
import TrainingParticipant from '@/models/TrainingParticipant'
import TrainingSeries from '@/models/TrainingSeries'
import TrainingTrainer from '@/models/TrainingTrainer'
import User from '@/models/User'

describe('User', () => {
  const json = {
    id: 1,
    email: 'a@b.de',
    firstName: 'Anna',
    familyName: 'Admin',
    birthdate: '1980-02-03',
    active: 1,
    groupIds: [1],
    roleNames: ['admin', 'trainer'],
    trainerBranchIds: [2],
    registered: 1,
    profileImageName: 'profile_images/a.jpg',
    absenceStart: '2030-01-01',
    absenceEnd: '2030-01-10',
    absenceReason: 'Urlaub',
  }

  it('maps the API payload (numeric flags become booleans)', () => {
    const user = User.from(JSON.stringify(json)) as User

    expect(user).toMatchObject({ id: 1, email: 'a@b.de', active: true, registered: true, isAdmin: true, isTrainer: true, absenceReason: 'Urlaub' })
    expect(user.getFullName()).toBe('Anna Admin')
    expect(user.getFullNameFamilyFirst()).toBe('Admin Anna')
  })

  it('treats active=0 / registered=0 as false and members as neither admin nor trainer', () => {
    const user = User.from(JSON.stringify({ ...json, active: 0, registered: 0, roleNames: ['member'] })) as User

    expect(user.active).toBe(false)
    expect(user.registered).toBe(false)
    expect(user.isAdmin).toBe(false)
    expect(user.isTrainer).toBe(false)
  })

  it('leaves the role flags undefined without role names', () => {
    const user = User.from(JSON.stringify({ ...json, roleNames: undefined })) as User

    expect(user.isAdmin).toBeUndefined()
  })

  it('returns null for invalid JSON, null and undefined', () => {
    expect(User.from('{nope')).toBeNull()
    expect(User.from(null)).toBeNull() // JSON.parse(null) is null -> property access throws -> caught
    expect(User.from(undefined)).toBeNull()
  })
})

describe('Branch / Group / SimpleUser', () => {
  it('formats names', () => {
    const branch = new Branch(1, 'Judo', 'JUD', '#fff')
    const group = new Group(2, 'Kids', 1, branch, [])
    const simple = new SimpleUser(3, 'Max', 'Muster', [2])

    expect(branch.getShortNameAndName()).toBe('Judo (JUD)')
    expect(group.getWithBranchName()).toBe('JUD | Kids')
    expect(simple.getFullName()).toBe('Max Muster')
    expect(simple.getFullNameFamilyFirst()).toBe('Muster Max')
  })
})

describe('Training models', () => {
  it('TrainingParticipant.from maps attend=1 to true', () => {
    const p = TrainingParticipant.from(JSON.stringify({ trainingId: 1, userId: 2, attend: 1, cancelreason: null }))

    expect(p).toMatchObject({ trainingId: 1, userId: 2, attend: true, cancelreason: null })
    expect(TrainingParticipant.from(JSON.stringify({ trainingId: 1, userId: 2, attend: 0 })).attend).toBe(false)
  })

  it('TrainingTrainer.from copies the accounting times', () => {
    const t = TrainingTrainer.from(JSON.stringify({ trainingId: 1, userId: 2, accountingTimeStart: 'a', accountingTimeEnd: 'b' }))

    expect(t).toMatchObject({ trainingId: 1, userId: 2, accountingTimeStart: 'a', accountingTimeEnd: 'b' })
  })

  it('Training.from expects the legacy "trainingParticipants" key (API sends "participants")', () => {
    // characterization: the model parses a payload shape the current API does not produce,
    // so it is only usable with the legacy format
    const training = Training.from(JSON.stringify({
      id: 5, start: 's', end: 'e', locationId: 1, groupIds: [1], contentIds: [], trainerIds: [9],
      trainingParticipants: [JSON.stringify({ trainingId: 5, userId: 2, attend: 1 })],
      comment: 'c', automaticAttend: 1,
    }))

    expect(training.id).toBe(5)
    expect(training.automaticAttend).toBe(true)
    expect(training.participants[0]).toBeInstanceOf(TrainingParticipant)
    expect(training.prepared).toBeNull()
    expect(() => Training.from(JSON.stringify({ id: 1, participants: [] }))).toThrow()
  })

  it('TrainingSeries.from maps the series payload', () => {
    const s = TrainingSeries.from({
      id: 1, startTime: '18:00', endTime: '19:30', locationId: 2, groupIds: [1], contentIds: [3], trainerIds: [4],
      comment: 'x', weekdays: [1, 3], deferUntil: '2030-01-01T00:00:00+01:00', automaticAttend: 1,
    })

    expect(s).toMatchObject({ id: 1, startTime: '18:00', weekdays: [1, 3], automaticAttend: true, deferUntil: '2030-01-01T00:00:00+01:00' })
    expect(TrainingSeries.from({ id: 2, automaticAttend: 0 }).automaticAttend).toBe(false)
  })
})
